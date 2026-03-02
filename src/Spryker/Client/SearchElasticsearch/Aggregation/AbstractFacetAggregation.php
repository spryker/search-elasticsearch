<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Aggregation;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Aggregation\AbstractSimpleAggregation;
use Elastica\Aggregation\Filter;
use Elastica\Aggregation\Nested;
use Elastica\Aggregation\Terms;
use Elastica\Query\Term;
use Generated\Shared\Transfer\FacetConfigTransfer;

abstract class AbstractFacetAggregation implements FacetAggregationInterface
{
    /**
     * @var string
     */
    public const FACET_VALUE = 'facet-value';

    /**
     * @var string
     */
    public const FACET_NAME = 'facet-name';

    /**
     * @var string
     */
    public const NAME_SUFFIX = '-name';

    /**
     * @var string
     */
    public const PATH_SEPARATOR = '.';

    protected function createNestedFacetAggregation(string $fieldName, AbstractAggregation $aggregation, ?string $path = null): AbstractAggregation
    {
        if ($path === null) {
            $path = $fieldName;
        }

        return (new Nested($fieldName, $path))
            ->addAggregation($aggregation);
    }

    protected function createFacetNameAggregation(string $fieldName, int $size): AbstractSimpleAggregation
    {
        $terms = (new Terms($fieldName . static::NAME_SUFFIX))
            ->setField($this->addNestedFieldPrefix($fieldName, static::FACET_NAME))
            ->setSize($size);

        return $terms;
    }

    protected function createStandaloneFacetNameAggregation(string $parentFieldName, string $fieldName): AbstractAggregation
    {
        $filterName = $this->addNestedFieldPrefix($parentFieldName, $fieldName);
        $filterName = $filterName . static::NAME_SUFFIX;

        return (new Filter($filterName))
            ->setFilter(new Term([
                $this->addNestedFieldPrefix($parentFieldName, static::FACET_NAME) => $fieldName,
            ]));
    }

    protected function addNestedFieldPrefix(string $nestedFieldName, string $fieldName): string
    {
        return $nestedFieldName . static::PATH_SEPARATOR . $fieldName;
    }

    protected function getNestedFieldName(FacetConfigTransfer $facetConfigTransfer): string
    {
        $nestedFieldName = $facetConfigTransfer->getFieldName();

        if ($facetConfigTransfer->getAggregationParams()) {
            $nestedFieldName = $this->addNestedFieldPrefix(
                $nestedFieldName,
                $facetConfigTransfer->getName(),
            );
        }

        return $nestedFieldName;
    }

    protected function applyAggregationParams(AbstractAggregation $aggregation, FacetConfigTransfer $facetConfigTransfer): AbstractAggregation
    {
        foreach ($facetConfigTransfer->getAggregationParams() as $aggregationParamKey => $aggregationParamValue) {
            $aggregation->setParam($aggregationParamKey, $aggregationParamValue);
        }

        return $aggregation;
    }
}
