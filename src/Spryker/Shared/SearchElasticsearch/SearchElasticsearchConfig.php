<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\SearchElasticsearch;

use Spryker\Shared\Kernel\AbstractSharedConfig;

class SearchElasticsearchConfig extends AbstractSharedConfig
{
    /**
     * @api
     *
     * Available facet types
     *
     * @var string
     */
    public const FACET_TYPE_ENUMERATION = 'enumeration';

    /**
     * @api
     *
     * @var string
     */
    public const FACET_TYPE_RANGE = 'range';

    /**
     * @api
     *
     * @var string
     */
    public const FACET_TYPE_PRICE_RANGE = 'price-range';

    /**
     * @api
     *
     * @var string
     */
    public const FACET_TYPE_CATEGORY = 'category';

    /**
     * @var array<string>
     */
    protected const SUPPORTED_SOURCE_IDENTIFIERS = [];

    protected const int DEFAULT_CONNECTION_CHECK_CACHE_TTL_SECONDS = 5;

    protected const int DEFAULT_CONNECTION_CHECK_CACHE_FAILURE_TTL_SECONDS = 1;

    protected const int DEFAULT_CONNECTION_CHECK_TIMEOUT_SECONDS = 2;

    protected const int DEFAULT_CONNECTION_CHECK_CONNECT_TIMEOUT_SECONDS = 1;

    protected const string CLIENT_CONFIG_KEY_TIMEOUT = 'timeout';

    protected const string CLIENT_CONFIG_KEY_CONNECT_TIMEOUT = 'connectTimeout';

    /**
     * Elastica builds one connection per entry under these keys and only falls back to the
     * top-level configuration when neither is present, so timeouts have to be written into each
     * entry to apply at all.
     *
     * @var array<string>
     */
    protected const array CLIENT_CONFIG_PER_CONNECTION_KEYS = ['connections', 'servers'];

    /**
     * @api
     *
     * @return array<string, mixed>
     */
    public function getClientConfig(): array
    {
        if ($this->getConfig()->hasValue(SearchElasticsearchConstants::CLIENT_CONFIGURATION)) {
            return $this->get(SearchElasticsearchConstants::CLIENT_CONFIGURATION);
        }

        if ($this->getConfig()->hasValue(SearchElasticsearchConstants::EXTRA)) {
            $config = $this->get(SearchElasticsearchConstants::EXTRA);
        }

        $config['transport'] = ucfirst($this->get(SearchElasticsearchConstants::TRANSPORT));
        $config['port'] = $this->get(SearchElasticsearchConstants::PORT);
        $config['host'] = $this->get(SearchElasticsearchConstants::HOST);

        $authHeader = (string)$this->get(SearchElasticsearchConstants::AUTH_HEADER, '');

        if ($authHeader !== '') {
            $config['headers'] = [
                'Authorization' => sprintf('Basic %s', $authHeader),
            ];
        }

        return $config;
    }

    /**
     * @api
     *
     * @return array<string>
     */
    public function getSupportedSourceIdentifiers(): array
    {
        return static::SUPPORTED_SOURCE_IDENTIFIERS;
    }

    /**
     * @api
     *
     * @return string
     */
    public function getIndexPrefix(): string
    {
        return $this->get(SearchElasticsearchConstants::INDEX_PREFIX, '');
    }

    /**
     * @api
     */
    public function getConnectionCheckCacheTtlSeconds(): int
    {
        return (int)$this->get(
            SearchElasticsearchConstants::CONNECTION_CHECK_CACHE_TTL_SECONDS,
            static::DEFAULT_CONNECTION_CHECK_CACHE_TTL_SECONDS,
        );
    }

    /**
     * @api
     */
    public function getConnectionCheckCacheFailureTtlSeconds(): int
    {
        return (int)$this->get(
            SearchElasticsearchConstants::CONNECTION_CHECK_CACHE_FAILURE_TTL_SECONDS,
            static::DEFAULT_CONNECTION_CHECK_CACHE_FAILURE_TTL_SECONDS,
        );
    }

    /**
     * The connection check gets its own Elastica client configuration so that its timeouts are
     * independent of the ones used for real search traffic.
     *
     * @api
     *
     * @return array<string, mixed>
     */
    public function getConnectionCheckClientConfig(): array
    {
        $clientConfig = $this->getClientConfig();
        $timeouts = $this->getConnectionCheckTimeouts();

        if ($timeouts === []) {
            return $clientConfig;
        }

        $hasPerConnectionEntries = false;

        foreach (static::CLIENT_CONFIG_PER_CONNECTION_KEYS as $perConnectionKey) {
            if (!isset($clientConfig[$perConnectionKey]) || !is_array($clientConfig[$perConnectionKey])) {
                continue;
            }

            $hasPerConnectionEntries = true;

            foreach ($clientConfig[$perConnectionKey] as $index => $connectionConfig) {
                $clientConfig[$perConnectionKey][$index] = array_merge((array)$connectionConfig, $timeouts);
            }
        }

        if ($hasPerConnectionEntries) {
            return $clientConfig;
        }

        return array_merge($clientConfig, $timeouts);
    }

    /**
     * @return array<string, int>
     */
    protected function getConnectionCheckTimeouts(): array
    {
        $timeouts = [];
        $timeoutSeconds = $this->getConnectionCheckTimeoutSeconds();

        if ($timeoutSeconds > 0) {
            $timeouts[static::CLIENT_CONFIG_KEY_TIMEOUT] = $timeoutSeconds;
        }

        $connectTimeoutSeconds = $this->getConnectionCheckConnectTimeoutSeconds();

        if ($connectTimeoutSeconds > 0) {
            $timeouts[static::CLIENT_CONFIG_KEY_CONNECT_TIMEOUT] = $connectTimeoutSeconds;
        }

        return $timeouts;
    }

    /**
     * @api
     */
    public function getConnectionCheckTimeoutSeconds(): int
    {
        return (int)$this->get(
            SearchElasticsearchConstants::CONNECTION_CHECK_TIMEOUT_SECONDS,
            static::DEFAULT_CONNECTION_CHECK_TIMEOUT_SECONDS,
        );
    }

    /**
     * @api
     */
    public function getConnectionCheckConnectTimeoutSeconds(): int
    {
        return (int)$this->get(
            SearchElasticsearchConstants::CONNECTION_CHECK_CONNECT_TIMEOUT_SECONDS,
            static::DEFAULT_CONNECTION_CHECK_CONNECT_TIMEOUT_SECONDS,
        );
    }

    /**
     * @api
     */
    public function getConnectionCheckCacheDirectory(): string
    {
        return (string)$this->get(
            SearchElasticsearchConstants::CONNECTION_CHECK_CACHE_DIRECTORY,
            sys_get_temp_dir(),
        );
    }
}
