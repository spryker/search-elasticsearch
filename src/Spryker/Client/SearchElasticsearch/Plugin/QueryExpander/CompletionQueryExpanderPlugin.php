<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Plugin\QueryExpander;

use Elastica\Query;
use Generated\Shared\Search\PageIndexMap;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\SearchElasticsearch\Exception\InvalidSearchQueryException;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryExpanderPluginInterface;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface;
use Spryker\Client\SearchExtension\Dependency\Plugin\SearchStringGetterInterface;

/**
 * @method \Spryker\Client\SearchElasticsearch\SearchElasticsearchFactory getFactory()
 */
class CompletionQueryExpanderPlugin extends AbstractPlugin implements QueryExpanderPluginInterface
{
    /**
     * @var string
     */
    protected const AGGREGATION_NAME = 'completion';

    /**
     * @var int
     */
    protected const SIZE = 10;

    /**
     * @var string
     */
    protected const SEARCH_WILDCARD = '.*';

    /**
     * {@inheritDoc}
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
        $searchQuery = $this->assertSearchStringGetterQuery($searchQuery);

        $query = $searchQuery->getSearchQuery();
        $this->addAggregation($query, $searchQuery->getSearchString());

        /** @phpstan-var \Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface */
        return $searchQuery;
    }

    /**
     * @param \Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface $searchQuery
     *
     * @throws \Spryker\Client\SearchElasticsearch\Exception\InvalidSearchQueryException
     *
     * @return \Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface|\Spryker\Client\SearchExtension\Dependency\Plugin\SearchStringGetterInterface
     */
    protected function assertSearchStringGetterQuery(QueryInterface $searchQuery)
    {
        if (!$searchQuery instanceof SearchStringGetterInterface) {
            throw new InvalidSearchQueryException(sprintf(
                'The base search query must implement %s in order to use %s.',
                SearchStringGetterInterface::class,
                static::class,
            ));
        }

        return $searchQuery;
    }

    /**
     * @param \Elastica\Query $query
     * @param string|null $searchString
     *
     * @return void
     */
    protected function addAggregation(Query $query, ?string $searchString): void
    {
        $termsAggregation = $this->getFactory()
            ->createAggregationBuilder()
            ->createTermsAggregation(static::AGGREGATION_NAME)
            ->setField(PageIndexMap::COMPLETION_TERMS)
            ->setSize(static::SIZE)
            ->setInclude($this->getRegexpQueryString($searchString));

        $query->addAggregation($termsAggregation);
    }

    /**
     * @param string|null $searchString
     *
     * @return string
     */
    protected function getRegexpQueryString(?string $searchString): string
    {
        if (!$searchString) {
            return '';
        }

        $searchString = mb_strtolower($searchString);
        $searchString = str_replace('"', '"\\""', $searchString);
        $searchString = preg_replace('/\s+/', sprintf('"%s"', static::SEARCH_WILDCARD), $searchString);

        if ($searchString) {
            return sprintf('%s"%s"%s', static::SEARCH_WILDCARD, $searchString, static::SEARCH_WILDCARD);
        }

        return '';
    }
}
