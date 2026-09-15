<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\SearchElasticsearch\Connection\Cache;

use Codeception\Test\Unit;
use Spryker\Client\SearchElasticsearch\Connection\Cache\FilesystemConnectionCheckCache;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group SearchElasticsearch
 * @group Connection
 * @group Cache
 * @group FilesystemConnectionCheckCacheTest
 * Add your own group annotations below this line
 */
class FilesystemConnectionCheckCacheTest extends Unit
{
    protected const string CACHE_KEY = 'connection-check';

    protected string $directory;

    protected function _before(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('sccc_test_', true);
        mkdir($this->directory, 0777, true);
    }

    protected function _after(): void
    {
        foreach ((array)glob($this->directory . DIRECTORY_SEPARATOR . '*') as $file) {
            unlink((string)$file);
        }

        rmdir($this->directory);
    }

    public function testStoredEntryIsReadBack(): void
    {
        // Arrange
        $cache = new FilesystemConnectionCheckCache($this->directory);

        // Act
        $cache->set(static::CACHE_KEY, ['isSuccessful' => true, 'expiresAt' => 123], 5);

        // Assert
        $entry = $cache->get(static::CACHE_KEY);
        $this->assertTrue($entry['isSuccessful']);
        $this->assertSame(123, $entry['expiresAt']);
    }

    /**
     * The interface promises the entry stops being returned after $ttlSeconds. The filesystem has
     * no expiry of its own, so without this the entry would outlive the container's usefulness and
     * a lock-contended probe could replay an arbitrarily old verdict.
     */
    public function testEntryIsNotReturnedOnceItsTtlHasPassed(): void
    {
        // Arrange
        $cache = new FilesystemConnectionCheckCache($this->directory);
        $cache->set(static::CACHE_KEY, ['isSuccessful' => true], 1);
        $files = (array)glob($this->directory . DIRECTORY_SEPARATOR . '*.cache');
        $entry = json_decode((string)file_get_contents((string)$files[0]), true);

        // Act
        $entry['evictAt'] = time() - 1;
        file_put_contents((string)$files[0], (string)json_encode($entry));

        // Assert
        $this->assertNull($cache->get(static::CACHE_KEY));
    }

    public function testDistinctKeysDoNotShareOneFile(): void
    {
        // Arrange
        $cache = new FilesystemConnectionCheckCache($this->directory);

        // Act
        $cache->set('first', ['isSuccessful' => true, 'expiresAt' => 1], 5);
        $cache->set('second', ['isSuccessful' => false, 'expiresAt' => 2], 5);

        // Assert
        $this->assertSame(1, $cache->get('first')['expiresAt']);
        $this->assertSame(2, $cache->get('second')['expiresAt']);
    }

    public function testReturnsNullWhenNothingWasStored(): void
    {
        // Arrange
        $cache = new FilesystemConnectionCheckCache($this->directory);

        // Act
        $entry = $cache->get(static::CACHE_KEY);

        // Assert
        $this->assertNull($entry);
    }

    public function testNothingIsStoredForANonPositiveTtl(): void
    {
        // Arrange
        $cache = new FilesystemConnectionCheckCache($this->directory);

        // Act
        $cache->set(static::CACHE_KEY, ['isSuccessful' => true], 0);

        // Assert
        $this->assertNull($cache->get(static::CACHE_KEY));
    }

    /**
     * The single-flight primitive: the second holder must be told the lock is taken.
     */
    public function testOnlyOneHolderAcquiresTheLock(): void
    {
        // Arrange
        $firstCache = new FilesystemConnectionCheckCache($this->directory);
        $secondCache = new FilesystemConnectionCheckCache($this->directory);

        // Act
        $isFirstAcquired = $firstCache->acquireLock(static::CACHE_KEY, 30);
        $isSecondAcquired = $secondCache->acquireLock(static::CACHE_KEY, 30);

        // Assert
        $this->assertTrue($isFirstAcquired);
        $this->assertFalse($isSecondAcquired);
    }

    public function testLockCanBeAcquiredAgainAfterRelease(): void
    {
        // Arrange
        $firstCache = new FilesystemConnectionCheckCache($this->directory);
        $secondCache = new FilesystemConnectionCheckCache($this->directory);
        $firstCache->acquireLock(static::CACHE_KEY, 30);

        // Act
        $firstCache->releaseLock(static::CACHE_KEY);

        // Assert
        $this->assertTrue($secondCache->acquireLock(static::CACHE_KEY, 30));
    }

    public function testIsNotAvailableForAMissingDirectory(): void
    {
        // Arrange
        $cache = new FilesystemConnectionCheckCache($this->directory . DIRECTORY_SEPARATOR . 'absent');

        // Act
        $isAvailable = $cache->isAvailable();

        // Assert
        $this->assertFalse($isAvailable);
    }

    /**
     * A health check must never fail because it could not write its own cache file, so every
     * operation against an unusable directory has to degrade quietly rather than throw.
     */
    public function testOperationsOnAnUnusableDirectoryDoNotThrow(): void
    {
        // Arrange
        $cache = new FilesystemConnectionCheckCache(DIRECTORY_SEPARATOR . 'proc' . DIRECTORY_SEPARATOR . 'absent-path');

        // Act
        $cache->set(static::CACHE_KEY, ['isSuccessful' => true], 5);
        $entry = $cache->get(static::CACHE_KEY);
        $isAcquired = $cache->acquireLock(static::CACHE_KEY, 30);
        $cache->releaseLock(static::CACHE_KEY);

        // Assert
        $this->assertNull($entry);
        $this->assertTrue($isAcquired, 'Expected the check to proceed uncached rather than serve an entry it does not have.');
    }

    public function testCorruptCacheFileIsTreatedAsAMiss(): void
    {
        // Arrange
        $cache = new FilesystemConnectionCheckCache($this->directory);
        $cache->set(static::CACHE_KEY, ['isSuccessful' => true], 5);
        $files = (array)glob($this->directory . DIRECTORY_SEPARATOR . '*.cache');
        file_put_contents((string)$files[0], 'not json at all');

        // Act
        $entry = $cache->get(static::CACHE_KEY);

        // Assert
        $this->assertNull($entry);
    }
}
