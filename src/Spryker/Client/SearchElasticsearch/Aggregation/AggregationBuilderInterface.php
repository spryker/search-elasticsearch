<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Aggregation;

use Elastica\Aggregation\Filter;
use Elastica\Aggregation\GlobalAggregation;
use Elastica\Aggregation\Stats;
use Elastica\Aggregation\Terms;
use Elastica\Aggregation\TopHits;

interface AggregationBuilderInterface
{
    public function createGlobalAggregation(string $name): GlobalAggregation;

    public function createFilterAggregation(string $name): Filter;

    public function createTermsAggregation(string $name): Terms;

    public function createStatsAggregation(string $name): Stats;

    public function createTopHitsAggregation(string $name): TopHits;
}
