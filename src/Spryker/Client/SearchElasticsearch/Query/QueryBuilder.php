<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Query;

use Elastica\Query\BoolQuery;
use Elastica\Query\MatchAll;
use Elastica\Query\MatchQuery;
use Elastica\Query\Nested;
use Elastica\Query\Range;
use Elastica\Query\Term;
use Elastica\Query\Terms;

class QueryBuilder implements QueryBuilderInterface
{
    public function createRangeQuery(string $fieldName, ?string $minValue, ?string $maxValue, string $greaterParam = 'gte', string $lessParam = 'lte'): Range
    {
        $arguments = [];

        if ($minValue !== null) {
            $arguments[$greaterParam] = $minValue;
        }

        if ($maxValue !== null) {
            $arguments[$lessParam] = $maxValue;
        }

        $rangeQuery = new Range();
        $rangeQuery->addField($fieldName, $arguments);

        return $rangeQuery;
    }

    public function createNestedQuery(string $fieldName): Nested
    {
        $nestedQuery = new Nested();

        return $nestedQuery->setPath($fieldName);
    }

    public function createTermQuery(string $field, string $value): Term
    {
        $termQuery = new Term();

        return $termQuery->setTerm($field, $value);
    }

    public function createTermsQuery(string $field, array $values): Terms
    {
        $termQuery = new Terms($field, $values);

        return $termQuery;
    }

    public function createBoolQuery(): BoolQuery
    {
        return new BoolQuery();
    }

    /**
     * @return \Elastica\Query\MatchQuery|\Elastica\Query\Match
     */
    public function createMatchQuery()
    {
        $matchQueryClassName = class_exists(MatchQuery::class)
            ? MatchQuery::class
            : '\Elastica\Query\Match';

        return new $matchQueryClassName();
    }

    public function createMatchAllQuery(): MatchAll
    {
        return new MatchAll();
    }
}
