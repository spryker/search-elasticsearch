<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Aggregation;

use Elastica\Aggregation\AbstractAggregation;
use Generated\Shared\Transfer\FacetConfigTransfer;
use Spryker\Client\SearchElasticsearch\SearchElasticsearchConfig;

class NumericFacetAggregation extends AbstractFacetAggregation
{
    /**
     * @var string
     */
    public const STATS_SUFFIX = '-stats';

    /**
     * @var \Generated\Shared\Transfer\FacetConfigTransfer
     */
    protected $facetConfigTransfer;

    /**
     * @var \Spryker\Client\SearchElasticsearch\Aggregation\AggregationBuilderInterface
     */
    protected $aggregationBuilder;

    /**
     * @var \Spryker\Client\SearchElasticsearch\SearchElasticsearchConfig
     */
    protected $searchConfig;

    public function __construct(
        FacetConfigTransfer $facetConfigTransfer,
        AggregationBuilderInterface $aggregationBuilder,
        SearchElasticsearchConfig $searchConfig
    ) {
        $this->facetConfigTransfer = $facetConfigTransfer;
        $this->aggregationBuilder = $aggregationBuilder;
        $this->searchConfig = $searchConfig;
    }

    public function createAggregation(): AbstractAggregation
    {
        $fieldName = $this->facetConfigTransfer->getFieldName();
        $nestedFieldName = $this->getNestedFieldName($this->facetConfigTransfer);

        $prefixedFieldName = $this->addNestedFieldPrefix($fieldName, static::FACET_VALUE);

        $facetValueStats = $this
            ->aggregationBuilder
            ->createStatsAggregation($nestedFieldName . static::STATS_SUFFIX)
            ->setField($prefixedFieldName);

        $facetValueStats = $this->applyAggregationParams($facetValueStats, $this->facetConfigTransfer);

        if ($this->facetConfigTransfer->getAggregationParams()) {
            $facetNameAgg = $this
                ->createStandaloneFacetNameAggregation(
                    $fieldName,
                    $this->facetConfigTransfer->getName(),
                );
        } else {
            $facetNameAgg = $this->createFacetNameAggregation($fieldName, $this->getFacetNameAggregationSize());
        }

        $facetNameAgg->addAggregation($facetValueStats);

        return $this->createNestedFacetAggregation($nestedFieldName, $facetNameAgg, $fieldName);
    }

    protected function getFacetNameAggregationSize(): int
    {
        return $this->searchConfig->getFacetNameAggregationSize();
    }
}
