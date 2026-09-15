<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Connection;

use Exception;
use Generated\Shared\Transfer\SearchConnectionResponseTransfer;
use Spryker\Client\SearchElasticsearch\Connection\Cache\ConnectionCheckCacheInterface;
use Spryker\Client\SearchElasticsearch\Exception\CachedConnectionCheckException;
use Spryker\Shared\Log\LoggerTrait;
use Throwable;

/**
 * Collapses a burst of connection checks into a single call to the search engine.
 *
 * Health check probes across a fleet tend to arrive in lockstep, and each check that reaches the
 * search engine occupies a PHP worker for as long as the engine takes to answer. Two mechanisms
 * keep that bounded:
 *
 * - a short TTL, so repeated probes within the window reuse the last result;
 * - a single-flight lock, so at most one worker per pool is ever waiting on the search engine.
 *   Workers that find the lock taken serve the last known result rather than queueing behind it,
 *   which is what stops a slow engine from consuming the whole worker pool.
 */
class CachedConnection implements ConnectionInterface
{
    use LoggerTrait;

    protected const string CACHE_KEY = 'spryker:search_elasticsearch:connection_check';

    protected const string ENTRY_KEY_EXPIRES_AT = 'expiresAt';

    protected const string ENTRY_KEY_IS_SUCCESSFUL = 'isSuccessful';

    protected const string ENTRY_KEY_RAW_RESPONSE = 'rawResponse';

    protected const string ENTRY_KEY_ERROR_MESSAGE = 'errorMessage';

    /**
     * How long a stale entry is retained beyond its own TTL so that it can still be served while
     * another worker refreshes it. Without this the lock-losers would have nothing to serve and
     * would have to call the search engine themselves, defeating the single-flight.
     */
    protected const int STALE_RETENTION_SECONDS = 300;

    /**
     * Bounds how long a single in-flight check may hold the lock. A worker killed mid-check must
     * not block refreshes for the rest of the pool's life. Advisory only for backends whose lock is
     * already tied to process lifetime, such as the flock-based one.
     */
    protected const int LOCK_TTL_SECONDS = 30;

    protected ConnectionInterface $connection;

    protected ConnectionCheckCacheInterface $connectionCheckCache;

    protected int $ttlSeconds;

    protected int $failureTtlSeconds;

    public function __construct(
        ConnectionInterface $connection,
        ConnectionCheckCacheInterface $connectionCheckCache,
        int $ttlSeconds,
        int $failureTtlSeconds
    ) {
        $this->connection = $connection;
        $this->connectionCheckCache = $connectionCheckCache;
        $this->ttlSeconds = $ttlSeconds;
        $this->failureTtlSeconds = $failureTtlSeconds;
    }

    public function checkConnection(): SearchConnectionResponseTransfer
    {
        $entry = $this->connectionCheckCache->get(static::CACHE_KEY);

        if ($entry !== null && !$this->isExpired($entry)) {
            return $this->replayEntry($entry);
        }

        if (!$this->connectionCheckCache->acquireLock(static::CACHE_KEY, static::LOCK_TTL_SECONDS)) {
            // Another worker is already talking to the search engine. Serve what we last knew
            // rather than adding a second concurrent call - but only while it is recent enough to
            // still mean something. An unbounded replay here would let a probe report healthy from
            // an entry written hours before the search engine went down.
            if ($entry !== null && !$this->isBeyondStaleRetention($entry)) {
                return $this->replayEntry($entry);
            }

            return $this->connection->checkConnection();
        }

        try {
            return $this->executeAndCache();
        } finally {
            $this->connectionCheckCache->releaseLock(static::CACHE_KEY);
        }
    }

    protected function executeAndCache(): SearchConnectionResponseTransfer
    {
        try {
            $searchConnectionResponseTransfer = $this->connection->checkConnection();
        } catch (Exception $exception) {
            // Only an Exception is cached. An Error is a programming fault, and replaying it later
            // as CachedConnectionCheckException would make the same fault surface as a fatal on one
            // probe and a tidy unhealthy response on the next, depending on which won the lock.
            $this->storeEntrySafely(
                [
                    static::ENTRY_KEY_IS_SUCCESSFUL => false,
                    static::ENTRY_KEY_ERROR_MESSAGE => $exception->getMessage(),
                ],
                $this->failureTtlSeconds,
            );

            throw $exception;
        }

        $this->storeEntrySafely(
            [
                static::ENTRY_KEY_IS_SUCCESSFUL => true,
                static::ENTRY_KEY_RAW_RESPONSE => $searchConnectionResponseTransfer->getRawResponse(),
            ],
            $this->ttlSeconds,
        );

        return $searchConnectionResponseTransfer;
    }

    /**
     * The cache is documented as never throwing, but it is a public extension point: a project
     * implementation that breaks that contract must not be able to replace the connection error
     * the caller is in the middle of reporting.
     *
     * @param array<string, mixed> $entry
     */
    protected function storeEntrySafely(array $entry, int $ttlSeconds): void
    {
        try {
            $this->storeEntry($entry, $ttlSeconds);
        } catch (Throwable $throwable) {
            $this->getLogger()->warning(
                'Could not store the search connection check result; the next check will run uncached.',
                ['exception' => $throwable],
            );
        }
    }

    /**
     * @param array<string, mixed> $entry
     */
    protected function isBeyondStaleRetention(array $entry): bool
    {
        $expiresAt = (int)($entry[static::ENTRY_KEY_EXPIRES_AT] ?? 0);

        return $expiresAt + static::STALE_RETENTION_SECONDS <= time();
    }

    /**
     * @param array<string, mixed> $entry
     */
    protected function storeEntry(array $entry, int $ttlSeconds): void
    {
        if ($ttlSeconds <= 0) {
            return;
        }

        $entry[static::ENTRY_KEY_EXPIRES_AT] = time() + $ttlSeconds;

        $this->connectionCheckCache->set(
            static::CACHE_KEY,
            $entry,
            $ttlSeconds + static::STALE_RETENTION_SECONDS,
        );
    }

    /**
     * The expiry travels inside the entry, and with the filesystem backend that entry is a file
     * anything running as this user could have written. An expiry further out than our own TTL
     * allows is therefore not trusted: without this, one bogus entry would pin the check to its
     * cached answer indefinitely.
     *
     * @param array<string, mixed> $entry
     */
    protected function isExpired(array $entry): bool
    {
        $expiresAt = (int)($entry[static::ENTRY_KEY_EXPIRES_AT] ?? 0);
        $now = time();

        if ($expiresAt > $now + max($this->ttlSeconds, $this->failureTtlSeconds)) {
            return true;
        }

        return $expiresAt <= $now;
    }

    /**
     * @param array<string, mixed> $entry
     *
     * @throws \Spryker\Client\SearchElasticsearch\Exception\CachedConnectionCheckException
     */
    protected function replayEntry(array $entry): SearchConnectionResponseTransfer
    {
        if (($entry[static::ENTRY_KEY_IS_SUCCESSFUL] ?? false) !== true) {
            throw new CachedConnectionCheckException(
                (string)($entry[static::ENTRY_KEY_ERROR_MESSAGE] ?? ''),
            );
        }

        $searchConnectionResponseTransfer = new SearchConnectionResponseTransfer();
        $searchConnectionResponseTransfer->setIsSuccessfull(true);
        $searchConnectionResponseTransfer->setRawResponse(
            $entry[static::ENTRY_KEY_RAW_RESPONSE] ?? [],
        );

        return $searchConnectionResponseTransfer;
    }
}
