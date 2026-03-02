<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Query;

use Elastica\Query\Nested;

abstract class AbstractNestedQuery implements NestedQueryInterface
{
    /**
     * @var string
     */
    public const FACET_NAME_SUFFIX = '.facet-name';

    /**
     * @var string
     */
    public const FACET_VALUE_SUFFIX = '.facet-value';

    /**
     * @var \Spryker\Client\SearchElasticsearch\Query\QueryBuilderInterface
     */
    protected $queryBuilder;

    public function __construct(QueryBuilderInterface $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    protected function bindMultipleNestedQuery(string $fieldName, array $queries): Nested
    {
        $boolQuery = $this->queryBuilder->createBoolQuery();
        foreach ($queries as $query) {
            $boolQuery->addFilter($query);
        }

        $nestedQuery = $this->queryBuilder
            ->createNestedQuery($fieldName)
            ->setQuery($boolQuery);

        return $nestedQuery;
    }
}
