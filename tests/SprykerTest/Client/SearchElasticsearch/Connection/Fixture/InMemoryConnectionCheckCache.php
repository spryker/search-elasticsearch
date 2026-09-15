<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\SearchElasticsearch\Connection\Fixture;

use Spryker\Client\SearchElasticsearch\Connection\Cache\ConnectionCheckCacheInterface;

/**
 * Stands in for APCu, which cannot be exercised from the test runner because `apc.enable_cli` is
 * off. Adds hooks for the two states that are otherwise impossible to reach deterministically:
 * an entry that has aged past its TTL, and a lock already held by another worker.
 */
class InMemoryConnectionCheckCache implements ConnectionCheckCacheInterface
{
    /**
     * The real backends evict on their own TTL. A fixture that never expires would let a test
     * assert behaviour the production code cannot actually deliver.
     *
     * @var string
     */
    protected const string ENTRY_KEY_EVICT_AT = 'evictAt';

    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $entries = [];

    /**
     * @var array<string, bool>
     */
    protected array $locks = [];

    protected bool $isLockAcquisitionBlocked = false;

    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        $entry = $this->entries[$key] ?? null;

        if ($entry === null || (int)($entry[static::ENTRY_KEY_EVICT_AT] ?? 0) <= time()) {
            return null;
        }

        return $entry;
    }

    /**
     * @param array<string, mixed> $entry
     */
    public function set(string $key, array $entry, int $ttlSeconds): void
    {
        if ($ttlSeconds <= 0) {
            return;
        }

        $entry[static::ENTRY_KEY_EVICT_AT] = time() + $ttlSeconds;
        $this->entries[$key] = $entry;
    }

    public function acquireLock(string $key, int $ttlSeconds): bool
    {
        if ($this->isLockAcquisitionBlocked || isset($this->locks[$key])) {
            return false;
        }

        $this->locks[$key] = true;

        return true;
    }

    public function releaseLock(string $key): void
    {
        unset($this->locks[$key]);
    }

    /**
     * Simulates another worker holding the single-flight lock.
     */
    public function blockLockAcquisition(): void
    {
        $this->isLockAcquisitionBlocked = true;
    }

    public function isLocked(): bool
    {
        return $this->locks !== [];
    }

    /**
     * Ages only the decorator's own expiry, leaving this backend's eviction deadline alone, so a
     * test can reach the state of a backend that does not honour its TTL.
     */
    public function advanceDecoratorExpiryOnlyBy(int $seconds): void
    {
        foreach ($this->entries as $key => $entry) {
            if (!isset($entry['expiresAt'])) {
                continue;
            }

            $this->entries[$key]['expiresAt'] = $entry['expiresAt'] + $seconds;
        }
    }

    /**
     * Shifts every stored entry's expiry, so a test can age an entry past its TTL without sleeping.
     */
    public function advanceStoredExpiryBy(int $seconds): void
    {
        foreach ($this->entries as $key => $entry) {
            if (!isset($entry['expiresAt'])) {
                continue;
            }

            $this->entries[$key]['expiresAt'] = $entry['expiresAt'] + $seconds;
            $this->entries[$key][static::ENTRY_KEY_EVICT_AT] = (int)($entry[static::ENTRY_KEY_EVICT_AT] ?? time()) + $seconds;
        }
    }
}
