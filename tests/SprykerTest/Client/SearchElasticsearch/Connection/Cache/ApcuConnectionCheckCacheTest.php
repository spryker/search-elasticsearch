<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\SearchElasticsearch\Connection\Cache;

use Codeception\Test\Unit;
use Spryker\Client\SearchElasticsearch\Connection\Cache\ApcuConnectionCheckCache;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group SearchElasticsearch
 * @group Connection
 * @group Cache
 * @group ApcuConnectionCheckCacheTest
 * Add your own group annotations below this line
 */
class ApcuConnectionCheckCacheTest extends Unit
{
    /**
     * The default CLI SAPI ships `apc.enable_cli=0`, so this cannot run in the normal suite. It is
     * a skip rather than an omission on purpose: the single-flight guarantee rests on `apcu_add()`
     * being atomic, and a silently absent test would look identical to a covered one.
     */
    protected function _before(): void
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            $this->markTestSkipped(
                'APCu is not enabled for this SAPI (apc.enable_cli=0 by default). '
                . 'Re-run with `php -d apc.enable_cli=1` to exercise the real APCu backend.',
            );
        }
    }

    public function testOnlyOneHolderAcquiresTheLock(): void
    {
        // Arrange
        $cache = new ApcuConnectionCheckCache();
        $key = uniqid('connection-check-', true);

        // Act
        $isFirstAcquired = $cache->acquireLock($key, 30);
        $isSecondAcquired = $cache->acquireLock($key, 30);

        // Assert
        $this->assertTrue($isFirstAcquired);
        $this->assertFalse($isSecondAcquired, 'A second acquire must fail while the lock is held.');

        $cache->releaseLock($key);
    }

    public function testStoredEntryIsReadBackAndKeyedIndependently(): void
    {
        // Arrange
        $cache = new ApcuConnectionCheckCache();
        $firstKey = uniqid('first-', true);
        $secondKey = uniqid('second-', true);

        // Act
        $cache->set($firstKey, ['isSuccessful' => true], 5);
        $cache->set($secondKey, ['isSuccessful' => false], 5);

        // Assert
        $this->assertTrue($cache->get($firstKey)['isSuccessful']);
        $this->assertFalse($cache->get($secondKey)['isSuccessful']);
    }

    public function testNothingIsStoredForANonPositiveTtl(): void
    {
        // Arrange
        $cache = new ApcuConnectionCheckCache();
        $key = uniqid('connection-check-', true);

        // Act
        $cache->set($key, ['isSuccessful' => true], 0);

        // Assert
        $this->assertNull($cache->get($key));
    }
}
