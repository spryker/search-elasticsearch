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
     * Available facet types
     *
     * @var string
     */
    public const FACET_TYPE_ENUMERATION = 'enumeration';

    /**
     * @var string
     */
    public const FACET_TYPE_RANGE = 'range';

    /**
     * @var string
     */
    public const FACET_TYPE_PRICE_RANGE = 'price-range';

    /**
     * @var string
     */
    public const FACET_TYPE_CATEGORY = 'category';

    /**
     * @var array<string>
     */
    protected const SUPPORTED_SOURCE_IDENTIFIERS = [];

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
}
