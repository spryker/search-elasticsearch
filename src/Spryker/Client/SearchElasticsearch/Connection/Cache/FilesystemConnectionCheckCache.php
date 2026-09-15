<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Connection\Cache;

use Spryker\Shared\Log\LoggerTrait;
use Throwable;

/**
 * A file in the container's own temporary directory, for runtimes without APCu.
 *
 * The directory must be container-local: a path on shared storage would let one task's result
 * answer for another task, which defeats a liveness probe. `sys_get_temp_dir()` is container-local
 * and is often RAM-backed in cloud runtimes.
 *
 * No operation here may throw. A health check that fails because it could not write its own cache
 * file would be an outage of our own making, so every failure degrades to "no cache" - the caller
 * then queries the search engine directly, exactly as it did before caching existed. Every such
 * degradation is logged, because a cache that silently stops working looks identical to one that
 * is working, while quietly restoring the thundering herd this class exists to prevent.
 */
class FilesystemConnectionCheckCache implements ConnectionCheckCacheInterface
{
    use LoggerTrait;

    protected const string CACHE_FILE_PREFIX = 'spryker_search_connection_check_';

    protected const string CACHE_FILE_EXTENSION = '.cache';

    protected const string LOCK_FILE_SUFFIX = '.lock';

    protected const string TEMPORARY_FILE_PREFIX = 'sccc';

    /**
     * Anything outside this set is replaced, so a key can never walk out of the directory.
     */
    protected const string UNSAFE_KEY_CHARACTERS_PATTERN = '/[^A-Za-z0-9_-]/';

    /**
     * Holds this backend's own expiry. The interface promises `$ttlSeconds` eviction and the
     * filesystem has none of its own, so it has to be carried in the payload and enforced on read.
     */
    protected const string ENTRY_KEY_EVICT_AT = 'evictAt';

    /**
     * @var resource|null
     */
    protected $lockHandle;

    public function __construct(protected string $directory)
    {
    }

    public function isAvailable(): bool
    {
        try {
            return $this->directory !== '' && is_dir($this->directory) && is_writable($this->directory);
        } catch (Throwable $throwable) {
            $this->logFailure('checking whether the cache directory is usable', $throwable);

            return false;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        try {
            $path = $this->getCacheFilePath($key);

            if (!is_file($path)) {
                return null;
            }

            $contents = file_get_contents($path);

            if ($contents === false || $contents === '') {
                return null;
            }

            $entry = json_decode($contents, true);

            if (!is_array($entry) || $this->isEvicted($entry)) {
                return null;
            }

            return $entry;
        } catch (Throwable $throwable) {
            $this->logFailure('reading the cache file', $throwable);

            return null;
        }
    }

    /**
     * @param array<string, mixed> $entry
     */
    public function set(string $key, array $entry, int $ttlSeconds): void
    {
        if ($ttlSeconds <= 0) {
            return;
        }

        try {
            $entry[static::ENTRY_KEY_EVICT_AT] = time() + $ttlSeconds;
            $payload = json_encode($entry);

            if ($payload === false) {
                $this->logFailure('encoding the cache entry', null);

                return;
            }

            $this->writeAtomically($this->getCacheFilePath($key), $payload);
        } catch (Throwable $throwable) {
            $this->logFailure('writing the cache file', $throwable);
        }
    }

    public function acquireLock(string $key, int $ttlSeconds): bool
    {
        try {
            if ($this->lockHandle !== null) {
                // Re-entering would orphan the held descriptor, and an orphaned flock is held for
                // the life of the process, blocking every later refresh in this pool.
                return false;
            }

            $lockHandle = fopen($this->getCacheFilePath($key) . static::LOCK_FILE_SUFFIX, 'c');

            if ($lockHandle === false) {
                $this->logFailure('opening the lock file', null);

                // Proceed with the check rather than serving an entry we are not entitled to.
                return true;
            }

            if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
                fclose($lockHandle);

                return false;
            }

            $this->lockHandle = $lockHandle;

            return true;
        } catch (Throwable $throwable) {
            $this->logFailure('acquiring the lock', $throwable);

            return true;
        }
    }

    public function releaseLock(string $key): void
    {
        if ($this->lockHandle === null) {
            return;
        }

        try {
            flock($this->lockHandle, LOCK_UN);
            fclose($this->lockHandle);
        } catch (Throwable $throwable) {
            $this->logFailure('releasing the lock', $throwable);
        }

        $this->lockHandle = null;
    }

    /**
     * @param array<string, mixed> $entry
     */
    protected function isEvicted(array $entry): bool
    {
        return (int)($entry[static::ENTRY_KEY_EVICT_AT] ?? 0) <= time();
    }

    /**
     * A reader must never observe a half-written entry, so the payload lands on a temporary file
     * first and is moved into place with a single atomic rename. `file_put_contents()` returns the
     * byte count rather than false on a short write, so comparing the count is what actually
     * catches a truncated write on a full filesystem.
     */
    protected function writeAtomically(string $path, string $contents): void
    {
        $temporaryPath = tempnam($this->directory, static::TEMPORARY_FILE_PREFIX);

        if ($temporaryPath === false) {
            $this->logFailure('creating a temporary file', null);

            return;
        }

        if (file_put_contents($temporaryPath, $contents) !== strlen($contents) || !rename($temporaryPath, $path)) {
            unlink($temporaryPath);
            $this->logFailure('moving the cache file into place', null);
        }
    }

    protected function getCacheFilePath(string $key): string
    {
        $safeKey = (string)preg_replace(static::UNSAFE_KEY_CHARACTERS_PATTERN, '_', $key);

        return rtrim($this->directory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . static::CACHE_FILE_PREFIX
            . $safeKey
            . static::CACHE_FILE_EXTENSION;
    }

    /**
     * Monolog opens its stream on first write and raises when the path is unwritable, which is
     * exactly the environment class this cache degrades for. Logging is therefore guarded too:
     * an unwritable log directory must not turn into an unhealthy search verdict.
     */
    protected function logFailure(string $operation, ?Throwable $throwable): void
    {
        try {
            $this->writeWarning($operation, $throwable);
        } catch (Throwable $loggingThrowable) {
            // Nothing further can be reported; the check still runs, uncached.
        }
    }

    protected function writeWarning(string $operation, ?Throwable $throwable): void
    {
        $this->getLogger()->warning(
            sprintf(
                'Search connection check cache is degraded while %s in "%s"; the check will run uncached.',
                $operation,
                $this->directory,
            ),
            ['exception' => $throwable],
        );
    }
}
