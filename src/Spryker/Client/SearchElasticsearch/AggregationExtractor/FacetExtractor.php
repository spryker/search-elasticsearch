<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\AggregationExtractor;

use ArrayObject;
use Generated\Shared\Transfer\FacetConfigTransfer;
use Generated\Shared\Transfer\FacetSearchResultTransfer;
use Generated\Shared\Transfer\FacetSearchResultValueTransfer;
use Spryker\Client\SearchElasticsearch\Aggregation\StringFacetAggregation;
use Spryker\Shared\Kernel\Transfer\TransferInterface;

class FacetExtractor extends AbstractAggregationExtractor implements AggregationExtractorInterface
{
    /**
     * @var string
     */
    public const DOC_COUNT = 'doc_count';

    /**
     * @var \Generated\Shared\Transfer\FacetConfigTransfer
     */
    protected $facetConfigTransfer;

    /**
     * @var \Spryker\Client\SearchElasticsearch\AggregationExtractor\FacetValueTransformerFactoryInterface
     */
    protected $facetValueTransformerFactory;

    /**
     * @var \Spryker\Client\SearchExtension\Dependency\Plugin\FacetSearchResultValueTransformerPluginInterface|null
     */
    protected $valueTransformerPlugin;

    /**
     * @param \Generated\Shared\Transfer\FacetConfigTransfer $facetConfigTransfer
     * @param \Spryker\Client\SearchElasticsearch\AggregationExtractor\FacetValueTransformerFactoryInterface $facetValueTransformerFactory
     */
    public function __construct(FacetConfigTransfer $facetConfigTransfer, FacetValueTransformerFactoryInterface $facetValueTransformerFactory)
    {
        $this->facetConfigTransfer = $facetConfigTransfer;
        $this->facetValueTransformerFactory = $facetValueTransformerFactory;
        $this->valueTransformerPlugin = $facetValueTransformerFactory->createTransformer($facetConfigTransfer);
    }

    /**
     * @param array<string, mixed> $aggregations
     * @param array<string, mixed> $requestParameters
     *
     * @return \Spryker\Shared\Kernel\Transfer\TransferInterface
     */
    public function extractDataFromAggregations(array $aggregations, array $requestParameters): TransferInterface
    {
        $parameterName = $this->facetConfigTransfer->getParameterName();
        $name = $this->facetConfigTransfer->getName();
        $fieldName = $this->facetConfigTransfer->getFieldName();

        $facetResultValueTransfers = $this->extractFacetData($aggregations, $name, $fieldName);

        $facetResultTransfer = new FacetSearchResultTransfer();
        $facetResultTransfer
            ->setName($name)
            ->setValues($facetResultValueTransfers)
            ->setConfig(clone $this->facetConfigTransfer);

        if (isset($requestParameters[$parameterName])) {
            $facetResultTransfer->setActiveValue($requestParameters[$parameterName]);
        }

        return $facetResultTransfer;
    }

    /**
     * @param array $aggregation
     * @param string $name
     * @param string $fieldName
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\FacetSearchResultValueTransfer>
     */
    protected function extractFacetData(array $aggregation, string $name, string $fieldName): ArrayObject
    {
        if ($this->facetConfigTransfer->getAggregationParams()) {
            return $this->extractStandaloneFacetDataBuckets($aggregation, $fieldName);
        }

        return $this->extractFacetDataBuckets($aggregation, $name, $fieldName);
    }

    /**
     * @param array $aggregation
     * @param string $name
     * @param string $fieldName
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\FacetSearchResultValueTransfer>
     */
    protected function extractFacetDataBuckets(array $aggregation, string $name, string $fieldName): ArrayObject
    {
        $facetResultValues = new ArrayObject();
        $nameFieldName = $this->getFieldNameWithNameSuffix($fieldName);
        $valueFieldName = $this->getFieldNameWithValueSuffix($fieldName);
        foreach ($aggregation[$nameFieldName]['buckets'] as $nameBucket) {
            if ($nameBucket['key'] !== $name) {
                continue;
            }

            foreach ($nameBucket[$valueFieldName]['buckets'] as $valueBucket) {
                $facetResultValues = $this->addBucketValueToFacetResult($valueBucket, $facetResultValues);
            }

            break;
        }

        return $facetResultValues;
    }

    /**
     * @param array $aggregation
     * @param string $fieldName
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\FacetSearchResultValueTransfer>
     */
    protected function extractStandaloneFacetDataBuckets(array $aggregation, string $fieldName): ArrayObject
    {
        $facetResultValues = new ArrayObject();
        $nestedFieldName = $this->addNestedFieldPrefix($fieldName, $this->facetConfigTransfer->getName());

        $nameFieldName = $this->getFieldNameWithNameSuffix($nestedFieldName);
        $valueFieldName = $this->getFieldNameWithValueSuffix($nestedFieldName);

        foreach ($aggregation[$nameFieldName][$valueFieldName]['buckets'] as $valueBucket) {
            $facetResultValues = $this->addBucketValueToFacetResult($valueBucket, $facetResultValues);
        }

        return $facetResultValues;
    }

    /**
     * @param array $valueBucket
     * @param \ArrayObject<int, \Generated\Shared\Transfer\FacetSearchResultValueTransfer> $facetResultValues
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\FacetSearchResultValueTransfer>
     */
    protected function addBucketValueToFacetResult(array $valueBucket, ArrayObject $facetResultValues): ArrayObject
    {
        $facetResultValueTransfer = new FacetSearchResultValueTransfer();
        $value = $this->getFacetValue($valueBucket);

        $facetResultValueTransfer
            ->setValue($value)
            ->setDocCount($valueBucket[static::DOC_COUNT]);

        $facetResultValues->append($facetResultValueTransfer);

        return $facetResultValues;
    }

    /**
     * @param string $fieldName
     *
     * @return string
     */
    protected function getFieldNameWithNameSuffix(string $fieldName): string
    {
        return $fieldName . StringFacetAggregation::NAME_SUFFIX;
    }

    /**
     * @param string $fieldName
     *
     * @return string
     */
    protected function getFieldNameWithValueSuffix(string $fieldName): string
    {
        return $fieldName . StringFacetAggregation::VALUE_SUFFIX;
    }

    /**
     * @param array $valueBucket
     *
     * @return mixed
     */
    protected function getFacetValue(array $valueBucket)
    {
        $value = $valueBucket['key'];

        if ($this->valueTransformerPlugin) {
            $value = $this->valueTransformerPlugin->transformForDisplay($value);
        }

        return $value;
    }
}
