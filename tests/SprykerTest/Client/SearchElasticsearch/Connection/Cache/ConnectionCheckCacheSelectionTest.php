<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\SearchElasticsearch\Connection\Cache;

use Codeception\Test\Unit;
use Spryker\Client\SearchElasticsearch\Connection\Cache\ConnectionCheckCacheInterface;
use Spryker\Client\SearchElasticsearch\Connection\Cache\NullConnectionCheckCache;
use Spryker\Client\SearchElasticsearch\SearchElasticsearchFactory;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group SearchElasticsearch
 * @group Connection
 * @group Cache
 * @group ConnectionCheckCacheSelectionTest
 * Add your own group annotations below this line
 */
class ConnectionCheckCacheSelectionTest extends Unit
{
    /**
     * This ladder decides whether the burst protection is active at all in a given runtime.
     * Inverting a branch here would silently return every deployment to one search-engine call
     * per probe - the outage this feature exists to prevent - with the suite still green.
     */
    public function testPrefersApcuWhenItIsAvailable(): void
    {
        // Arrange
        $factory = $this->createFactory(isApcuAvailable: true, isFilesystemAvailable: true);

        // Act
        $connectionCheckCache = $factory->createConnectionCheckCache();

        // Assert
        $this->assertSame('apcu', $connectionCheckCache->name);
    }

    public function testFallsBackToTheFilesystemWhenApcuIsUnavailable(): void
    {
        // Arrange
        $factory = $this->createFactory(isApcuAvailable: false, isFilesystemAvailable: true);

        // Act
        $connectionCheckCache = $factory->createConnectionCheckCache();

        // Assert
        $this->assertSame('filesystem', $connectionCheckCache->name);
    }

    public function testFallsBackToNoCachingWhenNeitherBackendIsUsable(): void
    {
        // Arrange
        $factory = $this->createFactory(isApcuAvailable: false, isFilesystemAvailable: false);

        // Act
        $connectionCheckCache = $factory->createConnectionCheckCache();

        // Assert
        $this->assertInstanceOf(NullConnectionCheckCache::class, $connectionCheckCache);
    }

    protected function createFactory(bool $isApcuAvailable, bool $isFilesystemAvailable): SearchElasticsearchFactory
    {
        return new class ($isApcuAvailable, $isFilesystemAvailable) extends SearchElasticsearchFactory {
            public function __construct(
                protected bool $isApcuAvailable,
                protected bool $isFilesystemAvailable
            ) {
            }

            public function createApcuConnectionCheckCache(): ConnectionCheckCacheInterface
            {
                return ConnectionCheckCacheSelectionTest::createStubCache('apcu', $this->isApcuAvailable);
            }

            public function createFilesystemConnectionCheckCache(): ConnectionCheckCacheInterface
            {
                return ConnectionCheckCacheSelectionTest::createStubCache('filesystem', $this->isFilesystemAvailable);
            }
        };
    }

    public static function createStubCache(string $name, bool $isAvailable): ConnectionCheckCacheInterface
    {
        return new class ($name, $isAvailable) implements ConnectionCheckCacheInterface {
            public function __construct(public string $name, protected bool $isAvailable)
            {
            }

            public function isAvailable(): bool
            {
                return $this->isAvailable;
            }

            /**
             * @return array<string, mixed>|null
             */
            public function get(string $key): ?array
            {
                return null;
            }

            /**
             * @param array<string, mixed> $entry
             */
            public function set(string $key, array $entry, int $ttlSeconds): void
            {
            }

            public function acquireLock(string $key, int $ttlSeconds): bool
            {
                return true;
            }

            public function releaseLock(string $key): void
            {
            }
        };
    }
}
