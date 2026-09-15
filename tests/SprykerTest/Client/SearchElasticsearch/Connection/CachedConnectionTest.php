<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\SearchElasticsearch\Connection;

use Codeception\Test\Unit;
use Exception;
use Generated\Shared\Transfer\SearchConnectionResponseTransfer;
use Spryker\Client\SearchElasticsearch\Connection\Cache\ConnectionCheckCacheInterface;
use Spryker\Client\SearchElasticsearch\Connection\Cache\NullConnectionCheckCache;
use Spryker\Client\SearchElasticsearch\Connection\CachedConnection;
use Spryker\Client\SearchElasticsearch\Connection\ConnectionInterface;
use Spryker\Client\SearchElasticsearch\Exception\CachedConnectionCheckException;
use SprykerTest\Client\SearchElasticsearch\Connection\Fixture\InMemoryConnectionCheckCache;
use Throwable;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group SearchElasticsearch
 * @group Connection
 * @group CachedConnectionTest
 * Add your own group annotations below this line
 */
class CachedConnectionTest extends Unit
{
    protected const int TTL_SECONDS = 5;

    protected const int FAILURE_TTL_SECONDS = 1;

    /**
     * Mirrors CachedConnection::STALE_RETENTION_SECONDS.
     */
    protected const int STALE_RETENTION_SECONDS = 300;

    public function testFirstCheckDelegatesToTheWrappedConnection(): void
    {
        // Arrange
        $connectionMock = $this->createCountingConnectionMock($callCount);
        $cachedConnection = $this->createCachedConnection($connectionMock, new InMemoryConnectionCheckCache());

        // Act
        $searchConnectionResponseTransfer = $cachedConnection->checkConnection();

        // Assert
        $this->assertSame(1, $callCount);
        $this->assertTrue($searchConnectionResponseTransfer->getIsSuccessfull());
    }

    /**
     * This is the property the whole change exists for: a burst of probes must not become a burst
     * of calls to the search engine.
     *
     * @return void
     */
    public function testRepeatedChecksWithinTheTtlHitTheSearchEngineOnce(): void
    {
        // Arrange
        $connectionMock = $this->createCountingConnectionMock($callCount);
        $cachedConnection = $this->createCachedConnection($connectionMock, new InMemoryConnectionCheckCache());

        // Act
        for ($i = 0; $i < 50; $i++) {
            $cachedConnection->checkConnection();
        }

        // Assert
        $this->assertSame(1, $callCount, 'Expected 50 probes within the TTL to collapse into one call.');
    }

    public function testCheckIsRepeatedOnceTheEntryExpires(): void
    {
        // Arrange
        $connectionCheckCache = new InMemoryConnectionCheckCache();
        $connectionMock = $this->createCountingConnectionMock($callCount);
        $cachedConnection = $this->createCachedConnection($connectionMock, $connectionCheckCache);
        $cachedConnection->checkConnection();

        // Act
        $connectionCheckCache->advanceStoredExpiryBy(-(static::TTL_SECONDS + 1));
        $cachedConnection->checkConnection();

        // Assert
        $this->assertSame(2, $callCount);
    }

    /**
     * A worker that finds the lock taken must serve what is known rather than queue behind the
     * in-flight call. Queueing is what lets a slow search engine consume the worker pool.
     *
     * @return void
     */
    public function testServesTheLastKnownResultWhileAnotherWorkerIsChecking(): void
    {
        // Arrange
        $connectionCheckCache = new InMemoryConnectionCheckCache();
        $connectionMock = $this->createCountingConnectionMock($callCount);
        $cachedConnection = $this->createCachedConnection($connectionMock, $connectionCheckCache);
        $cachedConnection->checkConnection();

        // Act
        $connectionCheckCache->advanceStoredExpiryBy(-(static::TTL_SECONDS + 1));
        $connectionCheckCache->blockLockAcquisition();
        $searchConnectionResponseTransfer = $cachedConnection->checkConnection();

        // Assert
        $this->assertSame(1, $callCount, 'Expected the stale entry to be served instead of a second call.');
        $this->assertTrue($searchConnectionResponseTransfer->getIsSuccessfull());
    }

    /**
     * With nothing cached yet there is nothing to serve, so the caller must still get a real
     * answer rather than a fabricated one.
     *
     * @return void
     */

    /**
     * A probe must never report healthy on the strength of an entry old enough to predate the
     * outage it is supposed to detect. The lock-contended path serves stale entries deliberately,
     * so the age bound is the only thing standing between "slightly out of date" and "lying".
     */
    public function testDoesNotReplayAnEntryAgedBeyondStaleRetention(): void
    {
        // Arrange
        $connectionCheckCache = new InMemoryConnectionCheckCache();
        $connectionMock = $this->createCountingConnectionMock($callCount);
        $cachedConnection = $this->createCachedConnection($connectionMock, $connectionCheckCache);
        $cachedConnection->checkConnection();

        // Act
        $connectionCheckCache->advanceDecoratorExpiryOnlyBy(-(static::STALE_RETENTION_SECONDS + static::TTL_SECONDS + 1));
        $connectionCheckCache->blockLockAcquisition();
        $cachedConnection->checkConnection();

        // Assert
        $this->assertSame(2, $callCount, 'Expected a real check rather than a replay of a long-dead entry.');
    }

    public function testChecksDirectlyWhenTheLockIsTakenAndNothingIsCached(): void
    {
        // Arrange
        $connectionCheckCache = new InMemoryConnectionCheckCache();
        $connectionCheckCache->blockLockAcquisition();
        $connectionMock = $this->createCountingConnectionMock($callCount);
        $cachedConnection = $this->createCachedConnection($connectionMock, $connectionCheckCache);

        // Act
        $cachedConnection->checkConnection();

        // Assert
        $this->assertSame(1, $callCount);
    }

    public function testFailureIsReplayedForSubsequentChecksWithinTheFailureTtl(): void
    {
        // Arrange
        $callCount = 0;
        $connectionMock = $this->createMock(ConnectionInterface::class);
        $connectionMock->method('checkConnection')->willReturnCallback(function () use (&$callCount): void {
            $callCount++;

            throw new Exception('cluster unreachable');
        });
        $cachedConnection = $this->createCachedConnection($connectionMock, new InMemoryConnectionCheckCache());

        try {
            $cachedConnection->checkConnection();
        } catch (Throwable $throwable) {
            // The first check is expected to surface the real failure.
        }

        // Act
        $replayedThrowable = null;

        try {
            $cachedConnection->checkConnection();
        } catch (Throwable $throwable) {
            $replayedThrowable = $throwable;
        }

        // Assert
        $this->assertInstanceOf(CachedConnectionCheckException::class, $replayedThrowable);
        $this->assertSame('cluster unreachable', $replayedThrowable->getMessage());
        $this->assertSame(1, $callCount, 'Expected the failing engine not to be re-queried within the failure TTL.');
    }

    /**
     * The runtime without a usable cache backend is represented by the null backend the factory
     * selects there, so this asserts the real production object rather than a fixture flag.
     */
    public function testDelegatesEveryCheckWhenNoSharedMemoryCacheIsAvailable(): void
    {
        // Arrange
        $connectionCheckCache = new NullConnectionCheckCache();
        $connectionMock = $this->createCountingConnectionMock($callCount);
        $cachedConnection = $this->createCachedConnection($connectionMock, $connectionCheckCache);

        // Act
        $cachedConnection->checkConnection();
        $cachedConnection->checkConnection();

        // Assert
        $this->assertSame(2, $callCount);
    }

    public function testLockIsReleasedAfterAFailedCheckSoTheNextRefreshCanProceed(): void
    {
        // Arrange
        $connectionCheckCache = new InMemoryConnectionCheckCache();
        $connectionMock = $this->createMock(ConnectionInterface::class);
        $connectionMock->method('checkConnection')->willThrowException(new Exception('boom'));
        $cachedConnection = $this->createCachedConnection($connectionMock, $connectionCheckCache);

        // Act
        try {
            $cachedConnection->checkConnection();
        } catch (Exception $exception) {
            // expected
        }

        // Assert
        $this->assertFalse($connectionCheckCache->isLocked(), 'Expected the single-flight lock to be released.');
    }

    protected function createCountingConnectionMock(?int &$callCount): ConnectionInterface
    {
        $callCount = 0;

        $connectionMock = $this->createMock(ConnectionInterface::class);
        $connectionMock
            ->method('checkConnection')
            ->willReturnCallback(function () use (&$callCount): SearchConnectionResponseTransfer {
                $callCount++;

                return (new SearchConnectionResponseTransfer())
                    ->setIsSuccessfull(true)
                    ->setRawResponse(['status' => 'green']);
            });

        return $connectionMock;
    }

    protected function createCachedConnection(
        ConnectionInterface $connection,
        ConnectionCheckCacheInterface $connectionCheckCache
    ): CachedConnection {
        return new CachedConnection(
            $connection,
            $connectionCheckCache,
            static::TTL_SECONDS,
            static::FAILURE_TTL_SECONDS,
        );
    }
}
