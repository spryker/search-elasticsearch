<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Connection\Cache;

/**
 * APCu is shared memory scoped to a single PHP-FPM pool, which is the correct scope for a health
 * check: a liveness probe asks about the task it is addressed to. A cache shared across tasks
 * would let one task's result mask another task's broken connection.
 */
class ApcuConnectionCheckCache implements ConnectionCheckCacheInterface
{
    protected const string LOCK_KEY_SUFFIX = ':lock';

    public function isAvailable(): bool
    {
        return function_exists('apcu_enabled') && apcu_enabled();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        $isSuccessful = false;
        $entry = apcu_fetch($key, $isSuccessful);

        if ($isSuccessful !== true || !is_array($entry)) {
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

        apcu_store($key, $entry, $ttlSeconds);
    }

    public function acquireLock(string $key, int $ttlSeconds): bool
    {
        return apcu_add($this->buildLockKey($key), 1, max($ttlSeconds, 1));
    }

    public function releaseLock(string $key): void
    {
        apcu_delete($this->buildLockKey($key));
    }

    protected function buildLockKey(string $key): string
    {
        return sprintf('%s%s', $key, static::LOCK_KEY_SUFFIX);
    }
}
