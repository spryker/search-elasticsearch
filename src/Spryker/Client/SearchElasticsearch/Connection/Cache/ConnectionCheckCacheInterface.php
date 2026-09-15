<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Connection\Cache;

interface ConnectionCheckCacheInterface
{
    /**
     * Specification:
     * - Returns whether this backend is usable in the current runtime.
     * - Read by the factory when it selects a backend, not by the connection check itself: a
     *   backend reporting false is simply never selected. An implementation that is selected and
     *   then cannot cache reports it through the degradation path instead.
     */
    public function isAvailable(): bool;

    /**
     * Specification:
     * - Returns the cached entry for the given key, or null when nothing is cached.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array;

    /**
     * Specification:
     * - Stores the entry under the given key for $ttlSeconds, after which it MUST NOT be returned
     *   by get() any more. A backend without its own expiry has to carry the deadline in the entry.
     * - A $ttlSeconds of 0 or less must not store anything.
     * - MUST NOT throw an exception. The caller invokes this while handling a connection
     *   failure; an exception raised here would replace the error it is about to report.
     *
     * @param array<string, mixed> $entry
     */
    public function set(string $key, array $entry, int $ttlSeconds): void;

    /**
     * Specification:
     * - Atomically acquires a lock, returning true only for the caller that acquired it.
     * - The lock MUST NOT outlive the worker that took it: either it expires after $ttlSeconds, or
     *   it is tied to process lifetime (as an flock is), so a worker that dies mid-check cannot
     *   block subsequent checks forever. A backend taking the second route may ignore $ttlSeconds.
     * - MUST NOT throw an exception; on failure return true so the caller performs a real
     *   check rather than serving an entry it is not entitled to.
     */
    public function acquireLock(string $key, int $ttlSeconds): bool;

    /**
     * Specification:
     * - Releases a lock previously acquired via acquireLock().
     * - MUST NOT throw an exception. This runs in the caller's finally block, where an
     *   exception would displace the error being reported and point an operator at the
     *   wrong subsystem.
     */
    public function releaseLock(string $key): void;
}
