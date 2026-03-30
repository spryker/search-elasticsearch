<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Search;

use Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface;

interface SearchInterface
{
    /**
     * @param \Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface $searchQuery
     * @param array<\Spryker\Client\SearchExtension\Dependency\Plugin\ResultFormatterPluginInterface> $resultFormatters
     * @param array<string, mixed> $requestParameters
     *
     * @return \Elastica\ResultSet|array
     */
    public function search(QueryInterface $searchQuery, array $resultFormatters = [], array $requestParameters = []);

    /**
     * @param array<string, \Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface> $searchQueries
     * @param array<string, array<\Spryker\Client\SearchExtension\Dependency\Plugin\ResultFormatterPluginInterface>> $resultFormattersPerQuery
     * @param array<string, mixed> $requestParameters
     *
     * @return array<string, mixed>
     */
    public function multiSearch(array $searchQueries, array $resultFormattersPerQuery, array $requestParameters = []): array;
}
