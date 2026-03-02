<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Query;

use Elastica\Query\BoolQuery;
use Elastica\Query\MatchAll;
use Elastica\Query\Nested;
use Elastica\Query\Range;
use Elastica\Query\Term;
use Elastica\Query\Terms;

interface QueryBuilderInterface
{
    public function createRangeQuery(string $fieldName, ?string $minValue, ?string $maxValue, string $greaterParam = 'gte', string $lessParam = 'lte'): Range;

    public function createNestedQuery(string $fieldName): Nested;

    public function createTermQuery(string $field, string $value): Term;

    public function createTermsQuery(string $field, array $values): Terms;

    public function createBoolQuery(): BoolQuery;

    /**
     * @return \Elastica\Query\MatchQuery|\Elastica\Query\Match
     */
    public function createMatchQuery();

    public function createMatchAllQuery(): MatchAll;
}
