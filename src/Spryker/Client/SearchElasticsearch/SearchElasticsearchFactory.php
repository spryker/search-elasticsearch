<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch;

use Elastica\Client;
use Generated\Shared\Search\PageIndexMap;
use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\SearchElasticsearch\Aggregation\AggregationBuilder;
use Spryker\Client\SearchElasticsearch\Aggregation\AggregationBuilderInterface;
use Spryker\Client\SearchElasticsearch\Aggregation\FacetAggregationFactory;
use Spryker\Client\SearchElasticsearch\Aggregation\FacetAggregationFactoryInterface;
use Spryker\Client\SearchElasticsearch\AggregationExtractor\AggregationExtractorFactory;
use Spryker\Client\SearchElasticsearch\AggregationExtractor\AggregationExtractorFactoryInterface;
use Spryker\Client\SearchElasticsearch\AggregationExtractor\FacetValueTransformerFactory;
use Spryker\Client\SearchElasticsearch\AggregationExtractor\FacetValueTransformerFactoryInterface;
use Spryker\Client\SearchElasticsearch\Config\FacetConfig;
use Spryker\Client\SearchElasticsearch\Config\FacetConfigInterface;
use Spryker\Client\SearchElasticsearch\Config\PaginationConfig;
use Spryker\Client\SearchElasticsearch\Config\PaginationConfigInterface;
use Spryker\Client\SearchElasticsearch\Config\SearchConfigBuilder;
use Spryker\Client\SearchElasticsearch\Config\SearchConfigBuilderInterface;
use Spryker\Client\SearchElasticsearch\Config\SearchConfigInterface;
use Spryker\Client\SearchElasticsearch\Config\SortConfig;
use Spryker\Client\SearchElasticsearch\Config\SortConfigInterface;
use Spryker\Client\SearchElasticsearch\Connection\Connection;
use Spryker\Client\SearchElasticsearch\Connection\ConnectionInterface;
use Spryker\Client\SearchElasticsearch\Dependency\Client\SearchElasticsearchToMoneyClientInterface;
use Spryker\Client\SearchElasticsearch\Dependency\Client\SearchElasticsearchToStoreClientInterface;
use Spryker\Client\SearchElasticsearch\Index\IndexNameResolver\IndexNameResolver;
use Spryker\Client\SearchElasticsearch\Index\IndexNameResolver\IndexNameResolverInterface;
use Spryker\Client\SearchElasticsearch\Index\SourceIdentifier;
use Spryker\Client\SearchElasticsearch\Index\SourceIdentifierInterface;
use Spryker\Client\SearchElasticsearch\Plugin\Query\SearchKeysQuery;
use Spryker\Client\SearchElasticsearch\Plugin\Query\SearchStringQuery;
use Spryker\Client\SearchElasticsearch\Query\QueryBuilder;
use Spryker\Client\SearchElasticsearch\Query\QueryBuilderInterface;
use Spryker\Client\SearchElasticsearch\Query\QueryFactory;
use Spryker\Client\SearchElasticsearch\Query\QueryFactoryInterface;
use Spryker\Client\SearchElasticsearch\Reader\DocumentReaderFactory;
use Spryker\Client\SearchElasticsearch\Reader\DocumentReaderFactoryInterface;
use Spryker\Client\SearchElasticsearch\Reader\DocumentReaderInterface;
use Spryker\Client\SearchElasticsearch\Search\LoggableSearch;
use Spryker\Client\SearchElasticsearch\Search\Search;
use Spryker\Client\SearchElasticsearch\Search\SearchInterface;
use Spryker\Client\SearchElasticsearch\SearchContextExpander\SearchContextExpander;
use Spryker\Client\SearchElasticsearch\SearchContextExpander\SearchContextExpanderInterface;
use Spryker\Client\SearchElasticsearch\Suggest\SuggestBuilder;
use Spryker\Client\SearchElasticsearch\Suggest\SuggestBuilderInterface;
use Spryker\Client\SearchElasticsearch\Writer\DocumentWriterFactory;
use Spryker\Client\SearchElasticsearch\Writer\DocumentWriterFactoryInterface;
use Spryker\Client\SearchElasticsearch\Writer\DocumentWriterInterface;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface;
use Spryker\Shared\SearchElasticsearch\Dependency\Client\SearchElasticsearchToLocaleClientInterface;
use Spryker\Shared\SearchElasticsearch\Dependency\Service\SearchElasticsearchToUtilEncodingServiceInterface;
use Spryker\Shared\SearchElasticsearch\ElasticaClient\ElasticaClientFactory;
use Spryker\Shared\SearchElasticsearch\ElasticaClient\ElasticaClientFactoryInterface;
use Spryker\Shared\SearchElasticsearch\Logger\ElasticsearchInMemoryLogger;
use Spryker\Shared\SearchElasticsearch\Logger\ElasticsearchLoggerInterface;
use Spryker\Shared\SearchElasticsearch\MappingType\MappingTypeSupportDetector;
use Spryker\Shared\SearchElasticsearch\MappingType\MappingTypeSupportDetectorInterface;
use Spryker\Shared\SearchExtension\SourceInterface;

/**
 * @method \Spryker\Client\SearchElasticsearch\SearchElasticsearchConfig getConfig()
 */
class SearchElasticsearchFactory extends AbstractFactory
{
    /**
     * @return \Spryker\Client\SearchElasticsearch\Search\SearchInterface
     */
    public function createSearch(): SearchInterface
    {
        if (!$this->getConfig()->isDevelopmentMode()) {
            return $this->createSearchClient();
        }

        return $this->createLoggableSearchClient();
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Search\SearchInterface
     */
    public function createSearchClient(): SearchInterface
    {
        return new Search(
            $this->getElasticaClient(),
        );
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Connection\ConnectionInterface
     */
    public function createConnection(): ConnectionInterface
    {
        return new Connection(
            $this->getElasticaClient(),
        );
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Search\SearchInterface
     */
    public function createLoggableSearchClient(): SearchInterface
    {
        return new LoggableSearch(
            $this->createSearchClient(),
            $this->createElasticsearchLogger(),
        );
    }

    /**
     * @return \Spryker\Shared\SearchElasticsearch\Logger\ElasticsearchLoggerInterface
     */
    public function createElasticsearchLogger(): ElasticsearchLoggerInterface
    {
        return new ElasticsearchInMemoryLogger(
            $this->getUtilEncodingService(),
            $this->getConfig()->getClientConfig(),
        );
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Index\IndexNameResolver\IndexNameResolverInterface
     */
    public function createIndexNameResolver(): IndexNameResolverInterface
    {
        return new IndexNameResolver(
            $this->getStoreClient(),
            $this->getConfig(),
        );
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Aggregation\AggregationBuilderInterface
     */
    public function createAggregationBuilder(): AggregationBuilderInterface
    {
        return new AggregationBuilder();
    }

    /**
     * @param string $searchString
     * @param int|null $limit
     * @param int|null $offset
     *
     * @return \Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface
     */
    public function createSearchKeysQuery(string $searchString, ?int $limit = null, ?int $offset = null): QueryInterface
    {
        return new SearchKeysQuery($searchString, $this->getConfig(), $limit, $offset);
    }

    /**
     * @param string $searchString
     * @param int|null $limit
     * @param int|null $offset
     *
     * @return \Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface
     */
    public function createSearchStringQuery(string $searchString, ?int $limit = null, ?int $offset = null): QueryInterface
    {
        return new SearchStringQuery($searchString, $limit, $offset);
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Query\QueryBuilderInterface
     */
    public function createQueryBuilder(): QueryBuilderInterface
    {
        return new QueryBuilder();
    }

    /**
     * @return \Spryker\Shared\SearchExtension\SourceInterface
     */
    protected function createSource(): SourceInterface
    {
        return new PageIndexMap();
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Aggregation\FacetAggregationFactoryInterface
     */
    public function createFacetAggregationFactory(): FacetAggregationFactoryInterface
    {
        return new FacetAggregationFactory(
            $this->createSource(),
            $this->createAggregationBuilder(),
            $this->getConfig(),
        );
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\AggregationExtractor\FacetValueTransformerFactoryInterface
     */
    public function createFacetValueTransformerFactory(): FacetValueTransformerFactoryInterface
    {
        return new FacetValueTransformerFactory();
    }

    /**
     * @return \Elastica\Client
     */
    public function getElasticaClient(): Client
    {
        return $this->createElasticaClientFactory()->createClient(
            $this->getConfig()->getClientConfig(),
        );
    }

    /**
     * @return \Spryker\Shared\SearchElasticsearch\ElasticaClient\ElasticaClientFactoryInterface
     */
    public function createElasticaClientFactory(): ElasticaClientFactoryInterface
    {
        return new ElasticaClientFactory();
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Suggest\SuggestBuilderInterface
     */
    public function createSuggestBuilder(): SuggestBuilderInterface
    {
        return new SuggestBuilder();
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\AggregationExtractor\AggregationExtractorFactoryInterface
     */
    public function createAggregationExtractorFactory(): AggregationExtractorFactoryInterface
    {
        return new AggregationExtractorFactory($this->getMoneyClient());
    }

    /**
     * @return array<\Spryker\Client\SearchExtension\Dependency\Plugin\SearchConfigExpanderPluginInterface>
     */
    public function getSearchConfigExpanderPlugins()
    {
        return $this->getProvidedDependency(SearchElasticsearchDependencyProvider::PLUGINS_SEARCH_CONFIG_EXPANDER);
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Dependency\Client\SearchElasticsearchToStoreClientInterface
     */
    public function getStoreClient(): SearchElasticsearchToStoreClientInterface
    {
        return $this->getProvidedDependency(SearchElasticsearchDependencyProvider::CLIENT_STORE);
    }

    /**
     * @return \Spryker\Shared\SearchElasticsearch\Dependency\Client\SearchElasticsearchToLocaleClientInterface
     */
    public function getLocaleClient(): SearchElasticsearchToLocaleClientInterface
    {
        return $this->getProvidedDependency(SearchElasticsearchDependencyProvider::CLIENT_LOCALE);
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Query\QueryFactoryInterface
     */
    public function createQueryFactory(): QueryFactoryInterface
    {
        return new QueryFactory(
            $this->createQueryBuilder(),
            $this->getMoneyClient(),
        );
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Index\SourceIdentifier
     */
    public function createSourceIdentifierChecker(): SourceIdentifierInterface
    {
        return new SourceIdentifier($this->getConfig());
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\SearchContextExpander\SearchContextExpanderInterface
     */
    public function createSearchContextExpander(): SearchContextExpanderInterface
    {
        return new SearchContextExpander(
            $this->createIndexNameResolver(),
        );
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Dependency\Client\SearchElasticsearchToMoneyClientInterface
     */
    public function getMoneyClient(): SearchElasticsearchToMoneyClientInterface
    {
        return $this->getProvidedDependency(SearchElasticsearchDependencyProvider::CLIENT_MONEY);
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Config\SearchConfigInterface
     */
    public function getSearchConfig(): SearchConfigInterface
    {
        return $this->createSearchConfigBuilder()->build();
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Config\SearchConfigBuilderInterface
     */
    public function createSearchConfigBuilder(): SearchConfigBuilderInterface
    {
        $searchConfigBuilder = new SearchConfigBuilder(
            $this->createFacetConfig(),
            $this->createSortConfig(),
            $this->createPaginationConfig(),
        );
        $searchConfigBuilder->setSearchConfigBuilderPlugins(
            $this->getSearchConfigBuilderPlugins(),
        );
        $searchConfigBuilder->setSearchConfigExpanderPlugins(
            $this->getSearchConfigExpanderPlugins(),
        );

        return $searchConfigBuilder;
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Config\FacetConfigInterface
     */
    public function createFacetConfig(): FacetConfigInterface
    {
        return new FacetConfig();
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Config\SortConfigInterface
     */
    public function createSortConfig(): SortConfigInterface
    {
        return new SortConfig();
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Config\PaginationConfigInterface
     */
    public function createPaginationConfig(): PaginationConfigInterface
    {
        return new PaginationConfig();
    }

    /**
     * @return array<\Spryker\Client\SearchExtension\Dependency\Plugin\SearchConfigBuilderPluginInterface>
     */
    public function getSearchConfigBuilderPlugins(): array
    {
        return $this->getProvidedDependency(SearchElasticsearchDependencyProvider::PLUGINS_SEARCH_CONFIG_BUILDER);
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Writer\DocumentWriterInterface
     */
    public function createDocumentWriter(): DocumentWriterInterface
    {
        return $this->createDocumentWriterFactory()->createDocumentWriter($this->getElasticaClient());
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Writer\DocumentWriterFactoryInterface
     */
    public function createDocumentWriterFactory(): DocumentWriterFactoryInterface
    {
        return new DocumentWriterFactory(
            $this->createMappingTypeSupportDetector(),
        );
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Reader\DocumentReaderInterface
     */
    public function createDocumentReader(): DocumentReaderInterface
    {
        return $this->createDocumentReaderFactory()->createDocumentReader($this->getElasticaClient());
    }

    /**
     * @return \Spryker\Client\SearchElasticsearch\Reader\DocumentReaderFactoryInterface
     */
    public function createDocumentReaderFactory(): DocumentReaderFactoryInterface
    {
        return new DocumentReaderFactory(
            $this->createMappingTypeSupportDetector(),
        );
    }

    /**
     * @return \Spryker\Shared\SearchElasticsearch\MappingType\MappingTypeSupportDetectorInterface
     */
    public function createMappingTypeSupportDetector(): MappingTypeSupportDetectorInterface
    {
        return new MappingTypeSupportDetector();
    }

    /**
     * @return \Spryker\Shared\SearchElasticsearch\Dependency\Service\SearchElasticsearchToUtilEncodingServiceInterface
     */
    public function getUtilEncodingService(): SearchElasticsearchToUtilEncodingServiceInterface
    {
        return $this->getProvidedDependency(SearchElasticsearchDependencyProvider::SERVICE_UTIL_ENCODING);
    }
}
