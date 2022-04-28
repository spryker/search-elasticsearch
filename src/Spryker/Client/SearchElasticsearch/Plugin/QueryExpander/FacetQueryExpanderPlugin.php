<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Plugin\QueryExpander;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Query;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Generated\Shared\Transfer\FacetConfigTransfer;
use InvalidArgumentException;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\SearchElasticsearch\Config\FacetConfigInterface;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryExpanderPluginInterface;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface;

/**
 * @method \Spryker\Client\SearchElasticsearch\SearchElasticsearchFactory getFactory()
 */
class FacetQueryExpanderPlugin extends AbstractPlugin implements QueryExpanderPluginInterface
{
    /**
     * @var string
     */
    public const AGGREGATION_FILTER_NAME = 'filter';

    /**
     * @var string
     */
    public const AGGREGATION_GLOBAL_PREFIX = 'global-';

    /**
     * {@inheritDoc}
     * - Applies facet filters to query
     * - Facet filter values that equal null, empty string or false are dropped other values are kept including 0(zero)
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
        $facetConfig = $this->getFactory()->getSearchConfig()->getFacetConfig();

        $query = $searchQuery->getSearchQuery();

        $facetFilters = $this->getFacetFilters($facetConfig, $requestParameters);

        $this->addFacetAggregationToQuery($query, $facetConfig, $facetFilters, $requestParameters);
        $this->addFacetFiltersToBoolQuery($query, $facetFilters);

        return $searchQuery;
    }

    /**
     * @param \Spryker\Client\SearchElasticsearch\Config\FacetConfigInterface $facetConfig
     * @param array<string, mixed> $requestParameters
     *
     * @return array<\Elastica\Query\AbstractQuery>
     */
    protected function getFacetFilters(FacetConfigInterface $facetConfig, array $requestParameters = []): array
    {
        $facetFilters = [];
        $activeFacetConfigTransfers = $facetConfig->getActive($requestParameters);

        foreach ($activeFacetConfigTransfers as $facetConfigTransfer) {
            $filterValue = $requestParameters[$facetConfigTransfer->getParameterName()] ?? null;
            $query = $this->createFacetQuery($facetConfigTransfer, $filterValue);

            if ($query !== null) {
                $facetFilters[$facetConfigTransfer->getName()] = $query;
            }
        }

        return $facetFilters;
    }

    /**
     * @param \Generated\Shared\Transfer\FacetConfigTransfer $facetConfigTransfer
     * @param mixed|null $filterValue
     *
     * @return \Elastica\Query\AbstractQuery|null
     */
    protected function createFacetQuery(FacetConfigTransfer $facetConfigTransfer, $filterValue): ?AbstractQuery
    {
        if ($facetConfigTransfer->getIsMultiValued() === true) {
            return $this->createMultiValuedFacetFilterQuery($facetConfigTransfer, $filterValue);
        }

        return $this->createFacetFilterQuery($facetConfigTransfer, $filterValue);
    }

    /**
     * @param \Generated\Shared\Transfer\FacetConfigTransfer $facetConfigTransfer
     * @param mixed $filterValue
     *
     * @return \Elastica\Query\AbstractQuery
     */
    protected function createMultiValuedFacetFilterQuery(FacetConfigTransfer $facetConfigTransfer, $filterValue): AbstractQuery
    {
        $boolQuery = $this
            ->getFactory()
            ->createQueryBuilder()
            ->createBoolQuery();

        if (!is_array($filterValue)) {
            $filterValue = [$filterValue];
        }

        foreach ($filterValue as $value) {
            $query = $this->createFacetFilterQuery($facetConfigTransfer, $value);

            if ($query !== null) {
                $boolQuery->addShould($query);
            }
        }

        return $boolQuery;
    }

    /**
     * @param \Generated\Shared\Transfer\FacetConfigTransfer $facetConfigTransfer
     * @param mixed $filterValue
     *
     * @return \Elastica\Query\AbstractQuery|null
     */
    protected function createFacetFilterQuery(FacetConfigTransfer $facetConfigTransfer, $filterValue): ?AbstractQuery
    {
        if ($this->isFilterValueEmpty($filterValue)) {
            return null;
        }

        $valueTransformerPlugin = $this->getFactory()
            ->createFacetValueTransformerFactory()
            ->createTransformer($facetConfigTransfer);

        if ($valueTransformerPlugin) {
            $filterValue = $valueTransformerPlugin->transformFromDisplay($filterValue);
        }

        $query = $this->getFactory()->createQueryFactory()->createQuery($facetConfigTransfer, $filterValue);

        return $query;
    }

    /**
     * @param \Elastica\Query $query
     * @param \Spryker\Client\SearchElasticsearch\Config\FacetConfigInterface $facetConfig
     * @param array<\Elastica\Query\AbstractQuery> $facetFilters
     * @param array<string, mixed> $requestParameters
     *
     * @return void
     */
    protected function addFacetAggregationToQuery(Query $query, FacetConfigInterface $facetConfig, array $facetFilters, array $requestParameters): void
    {
        $boolQuery = $this->getBoolQuery($query);

        $activeFilters = $facetConfig->getActiveParamNames($requestParameters);

        foreach ($facetConfig->getAll() as $facetConfigTransfer) {
            $facetAggregation = $this
                ->getFactory()
                ->createFacetAggregationFactory()
                ->create($facetConfigTransfer)
                ->createAggregation();

            $query->addAggregation($facetAggregation);

            if (in_array($facetConfigTransfer->getName(), $activeFilters, true)) {
                $globalAggregation = $this->createGlobalAggregation($facetFilters, $facetConfigTransfer, $boolQuery, $facetAggregation);

                $query->addAggregation($globalAggregation);
            }
        }
    }

    /**
     * @param \Elastica\Query $query
     * @param array<\Elastica\Query\AbstractQuery> $facetFilters
     *
     * @return void
     */
    protected function addFacetFiltersToBoolQuery(Query $query, array $facetFilters): void
    {
        $boolQuery = $this->getBoolQuery($query);

        foreach ($facetFilters as $facetFilter) {
            $boolQuery->addFilter($facetFilter);
        }
    }

    /**
     * @param array<\Elastica\Query\AbstractQuery> $facetFilters
     * @param \Generated\Shared\Transfer\FacetConfigTransfer $facetConfigTransfer
     * @param \Elastica\Query\BoolQuery $boolQuery
     * @param \Elastica\Aggregation\AbstractAggregation $facetAggregation
     *
     * @return \Elastica\Aggregation\AbstractAggregation
     */
    protected function createGlobalAggregation(
        array $facetFilters,
        FacetConfigTransfer $facetConfigTransfer,
        BoolQuery $boolQuery,
        AbstractAggregation $facetAggregation
    ): AbstractAggregation {
        $aggregationFilterQuery = $this->getGlobalAggregationFilters($facetConfigTransfer, $boolQuery, $facetFilters);

        $filterAggregation = $this
            ->getFactory()
            ->createAggregationBuilder()
            ->createFilterAggregation(static::AGGREGATION_FILTER_NAME)
            ->setFilter($aggregationFilterQuery)
            ->addAggregation($facetAggregation);

        $globalAggregation = $this
            ->getFactory()
            ->createAggregationBuilder()
            ->createGlobalAggregation(static::AGGREGATION_GLOBAL_PREFIX . $facetConfigTransfer->getName());

        $globalAggregation
            ->addAggregation($filterAggregation);

        return $globalAggregation;
    }

    /**
     * @param \Generated\Shared\Transfer\FacetConfigTransfer $facetConfigTransfer
     * @param \Elastica\Query\BoolQuery $boolQuery
     * @param array<\Elastica\Query\AbstractQuery> $facetFilters
     *
     * @return \Elastica\Query\BoolQuery
     */
    protected function getGlobalAggregationFilters(FacetConfigTransfer $facetConfigTransfer, BoolQuery $boolQuery, array $facetFilters): BoolQuery
    {
        $aggregationFilterQuery = clone $boolQuery;

        // add filters for facet aggregation which is not related to the current facet so we can get the right counts
        foreach ($facetFilters as $name => $query) {
            if ($name !== $facetConfigTransfer->getName()) {
                $aggregationFilterQuery->addFilter($query);
            }
        }

        return $aggregationFilterQuery;
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
            throw new InvalidArgumentException(sprintf('Facet filters available only with %s, got: %s', BoolQuery::class, get_class($boolQuery)));
        }

        return $boolQuery;
    }

    /**
     * @param mixed $filterValue
     *
     * @return bool
     */
    protected function isFilterValueEmpty($filterValue): bool
    {
        if ($this->isFilterValueEmptyArray($filterValue)) {
            return true;
        }

        return !$filterValue && !is_numeric($filterValue);
    }

    /**
     * @param mixed $filterValue
     *
     * @return bool
     */
    protected function isFilterValueEmptyArray($filterValue): bool
    {
        if (is_array($filterValue) && !array_filter($filterValue, 'strlen')) {
            return true;
        }

        return false;
    }
}
