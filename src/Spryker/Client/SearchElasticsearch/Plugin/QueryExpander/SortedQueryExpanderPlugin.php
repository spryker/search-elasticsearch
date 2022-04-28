<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Plugin\QueryExpander;

use Elastica\Query;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryExpanderPluginInterface;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface;

/**
 * @method \Spryker\Client\SearchElasticsearch\SearchElasticsearchFactory getFactory()
 */
class SortedQueryExpanderPlugin extends AbstractPlugin implements QueryExpanderPluginInterface
{
    /**
     * @var string
     */
    protected const KEY_UNMAPPED_TYPE = 'unmapped_type';

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface $searchQuery
     * @param array<string, mixed> $requestParameters
     *
     * @return \Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface
     */
    public function expandQuery(QueryInterface $searchQuery, array $requestParameters = []): QueryInterface
    {
        $this->addSortingToQuery($searchQuery->getSearchQuery(), $requestParameters);

        return $searchQuery;
    }

    /**
     * @param \Elastica\Query $query
     * @param array<string, mixed> $requestParameters
     *
     * @return void
     */
    protected function addSortingToQuery(Query $query, array $requestParameters): void
    {
        $sortConfig = $this->getFactory()->getSearchConfig()->getSortConfig();
        $sortParamName = $sortConfig->getActiveParamName($requestParameters);
        $sortConfigTransfer = $sortConfig->get($sortParamName);

        if ($sortConfigTransfer === null) {
            return;
        }

        $nestedSortField = $sortConfigTransfer->getFieldName() . '.' . $sortConfigTransfer->getName();
        $sortRules = [
            $nestedSortField => [
                'order' => $sortConfig->getSortDirection($sortParamName),
                'mode' => 'min',
            ],
        ];

        if ($sortConfigTransfer->getUnmappedType()) {
            $sortRules[$nestedSortField][static::KEY_UNMAPPED_TYPE] = $sortConfigTransfer->getUnmappedType();
        }

        $query->addSort($sortRules);
    }
}
