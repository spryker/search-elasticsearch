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

class AggregationBuilder implements AggregationBuilderInterface
{
    public function createGlobalAggregation(string $name): GlobalAggregation
    {
        return new GlobalAggregation($name);
    }

    public function createFilterAggregation(string $name): Filter
    {
        return new Filter($name);
    }

    public function createTermsAggregation(string $name): Terms
    {
        return new Terms($name);
    }

    public function createStatsAggregation(string $name): Stats
    {
        return new Stats($name);
    }

    public function createTopHitsAggregation(string $name): TopHits
    {
        return new TopHits($name);
    }
}
