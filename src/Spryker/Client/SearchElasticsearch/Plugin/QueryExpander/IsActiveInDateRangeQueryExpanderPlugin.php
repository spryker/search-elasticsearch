<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Plugin\QueryExpander;

use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Exists;
use Elastica\Query\Range;
use Generated\Shared\Search\PageIndexMap;
use InvalidArgumentException;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryExpanderPluginInterface;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface;

/**
 * @method \Spryker\Client\SearchElasticsearch\SearchElasticsearchFactory getFactory()
 */
class IsActiveInDateRangeQueryExpanderPlugin extends AbstractPlugin implements QueryExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Expands range query with active_from and active_to fields.
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
        $this->addIsActiveInDateRangeFilterToQuery($searchQuery->getSearchQuery());

        return $searchQuery;
    }

    /**
     * @param \Elastica\Query $query
     *
     * @return void
     */
    protected function addIsActiveInDateRangeFilterToQuery(Query $query): void
    {
        $boolQuery = $this->getBoolQuery($query);

        $boolQuery->addMust($this->createActiveFromQuery());
        $boolQuery->addMust($this->createActiveToQuery());
    }

    /**
     * @param \Elastica\Query $query
     *
     * @throws \InvalidArgumentException
     *
     * @return \Elastica\Query\BoolQuery
     */
    protected function getBoolQuery(Query $query): BoolQuery
    {
        $boolQuery = $query->getQuery();
        if (!$boolQuery instanceof BoolQuery) {
            /** @phpstan-var object $boolQuery */
            throw new InvalidArgumentException(sprintf(
                'Is Active In Date Range query expander available only with %s, got: %s',
                BoolQuery::class,
                get_class($boolQuery),
            ));
        }

        return $boolQuery;
    }

    /**
     * @return \Elastica\Query\BoolQuery
     */
    protected function createActiveFromQuery(): BoolQuery
    {
        $rangeFromQuery = new Range();
        $rangeFromQuery->addField(
            PageIndexMap::ACTIVE_FROM,
            ['lte' => 'now'],
        );

        $missingFrom = $this->getFactory()
            ->createQueryBuilder()
            ->createBoolQuery()
            ->addMustNot(new Exists(PageIndexMap::ACTIVE_FROM));

        $boolFromQuery = $this->getFactory()
            ->createQueryBuilder()
            ->createBoolQuery()
            ->addShould($rangeFromQuery)
            ->addShould($missingFrom);

        return $boolFromQuery;
    }

    /**
     * @return \Elastica\Query\BoolQuery
     */
    protected function createActiveToQuery(): BoolQuery
    {
        $rangeToQuery = new Range();
        $rangeToQuery->addField(
            PageIndexMap::ACTIVE_TO,
            ['gte' => 'now'],
        );

        $missingTo = $this->getFactory()
            ->createQueryBuilder()
            ->createBoolQuery()
            ->addMustNot(new Exists(PageIndexMap::ACTIVE_TO));

        $boolToQuery = $this->getFactory()
            ->createQueryBuilder()
            ->createBoolQuery()
            ->addShould($rangeToQuery)
            ->addShould($missingTo);

        return $boolToQuery;
    }
}
