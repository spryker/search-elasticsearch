<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Search;

use Elastica\Client;
use Elastica\Exception\ResponseException;
use Elastica\Index;
use Elastica\Multi\Search as MultiSearch;
use Elastica\ResultSet;
use Elastica\Search as ElasticaSearch;
use Generated\Shared\Transfer\SearchContextTransfer;
use Spryker\Client\SearchElasticsearch\Exception\InvalidSearchQueryException;
use Spryker\Client\SearchElasticsearch\Exception\SearchResponseException;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface;
use Spryker\Client\SearchExtension\Dependency\Plugin\SearchContextAwareQueryInterface;

class Search implements SearchInterface
{
    /**
     * @var \Elastica\Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param \Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface $searchQuery
     * @param array<\Spryker\Client\SearchExtension\Dependency\Plugin\ResultFormatterPluginInterface> $resultFormatters
     * @param array<string, mixed> $requestParameters
     *
     * @return \Elastica\ResultSet|array
     */
    public function search(QueryInterface $searchQuery, array $resultFormatters = [], array $requestParameters = [])
    {
        $rawSearchResult = $this->executeQuery($searchQuery);

        if (!$resultFormatters) {
            return $rawSearchResult;
        }

        return $this->formatSearchResults($resultFormatters, $rawSearchResult, $requestParameters);
    }

    /**
     * @param array<\Spryker\Client\SearchExtension\Dependency\Plugin\ResultFormatterPluginInterface> $resultFormatters
     * @param \Elastica\ResultSet $rawSearchResult
     * @param array<string, mixed> $requestParameters
     *
     * @return array
     */
    protected function formatSearchResults(array $resultFormatters, ResultSet $rawSearchResult, array $requestParameters): array
    {
        $formattedSearchResult = [];

        foreach ($resultFormatters as $resultFormatter) {
            $formattedSearchResult[$resultFormatter->getName()] = $resultFormatter->formatResult($rawSearchResult, $requestParameters);
        }

        return $formattedSearchResult;
    }

    /**
     * @param \Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface $query
     *
     * @throws \Spryker\Client\SearchElasticsearch\Exception\SearchResponseException
     *
     * @return \Elastica\ResultSet
     */
    protected function executeQuery(QueryInterface $query): ResultSet
    {
        $searchContext = $this->getSearchContext($query);

        try {
            $index = $this->getIndexForQueryFromSearchContext($searchContext);
            $rawSearchResult = $index->search(
                $query->getSearchQuery(),
            );
        } catch (ResponseException $e) {
            $rawQuery = json_encode($query->getSearchQuery()->toArray());

            throw new SearchResponseException(
                sprintf('Search failed with the following reason: %s. Query: %s', $e->getMessage(), $rawQuery),
                $e->getCode(),
                $e,
            );
        }

        return $rawSearchResult;
    }

    /**
     * @deprecated Will be replaced with inline usage when SearchContextAwareQueryInterface is merged into QueryInterface.
     *
     * @param \Spryker\Client\SearchExtension\Dependency\Plugin\SearchContextAwareQueryInterface|\Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface $searchQuery
     *
     * @throws \Spryker\Client\SearchElasticsearch\Exception\InvalidSearchQueryException
     *
     * @return \Generated\Shared\Transfer\SearchContextTransfer
     */
    protected function getSearchContext($searchQuery): SearchContextTransfer
    {
        if (!$searchQuery instanceof SearchContextAwareQueryInterface) {
            throw new InvalidSearchQueryException(
                sprintf(
                    'Query class %s doesn\'t implement %s interface.',
                    get_class($searchQuery),
                    SearchContextAwareQueryInterface::class,
                ),
            );
        }

        return $searchQuery->getSearchContext();
    }

    protected function getIndexForQueryFromSearchContext(SearchContextTransfer $searchContextTransfer): Index
    {
        $indexName = $this->getIndexName($searchContextTransfer);

        return $this->client->getIndex($indexName);
    }

    protected function getIndexName(SearchContextTransfer $searchContextTransfer): string
    {
        $this->assertSearchContextTransferHasIndexName($searchContextTransfer);

        return $searchContextTransfer
            ->getElasticsearchContext()
            ->requireIndexName()
            ->getIndexName();
    }

    protected function assertSearchContextTransferHasIndexName(SearchContextTransfer $searchContextTransfer): void
    {
        $searchContextTransfer->requireElasticsearchContext()->getElasticsearchContext()->requireIndexName();
    }

    /**
     * @param array<string, \Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface> $searchQueries
     * @param array<string, array<\Spryker\Client\SearchExtension\Dependency\Plugin\ResultFormatterPluginInterface>> $resultFormattersPerQuery
     * @param array<string, mixed> $requestParameters
     *
     * @throws \Spryker\Client\SearchElasticsearch\Exception\SearchResponseException
     *
     * @return array<string, mixed>
     */
    public function multiSearch(array $searchQueries, array $resultFormattersPerQuery, array $requestParameters = []): array
    {
        $multiSearch = new MultiSearch($this->client);

        foreach ($searchQueries as $key => $searchQuery) {
            $searchContext = $this->getSearchContext($searchQuery);
            $index = $this->getIndexForQueryFromSearchContext($searchContext);

            $elasticaSearch = new ElasticaSearch($this->client);
            $elasticaSearch->addIndex($index);
            $elasticaSearch->setQuery($searchQuery->getSearchQuery());

            $multiSearch->addSearch($elasticaSearch, $key);
        }

        try {
            $multiResultSet = $multiSearch->search();
        } catch (ResponseException $e) {
            throw new SearchResponseException(
                sprintf('Multi search failed with the following reason: %s', $e->getMessage()),
                $e->getCode(),
                $e,
            );
        }

        $results = [];

        foreach ($multiResultSet->getResultSets() as $key => $resultSet) {
            $formatters = $resultFormattersPerQuery[$key] ?? [];

            if (!$formatters) {
                $results[$key] = $resultSet;

                continue;
            }

            $results[$key] = $this->formatSearchResults($formatters, $resultSet, $requestParameters);
        }

        return $results;
    }
}
