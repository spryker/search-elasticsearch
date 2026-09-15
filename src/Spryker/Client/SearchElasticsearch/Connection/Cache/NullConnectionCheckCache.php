<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Connection\Cache;

/**
 * Used when no shared-memory cache is available, e.g. under CLI where `apc.enable_cli` is off.
 * Every check is then executed against the search engine, which is the pre-caching behaviour.
 *
 * `isAvailable()` returns false because this is not a usable cache: it reports itself as such so
 * the factory never *selects* it over a real backend, and reaches it only as the last resort. Once
 * in use, `get()` returning null and `acquireLock()` returning true make every check run live.
 */
class NullConnectionCheckCache implements ConnectionCheckCacheInterface
{
    public function isAvailable(): bool
    {
        return false;
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
}
