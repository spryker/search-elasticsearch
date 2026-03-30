<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\SearchElasticsearch;

use Codeception\Test\Unit;
use Elastica\Query;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\MultiMatch;
use Elastica\Query\QueryString;
use Elastica\ResultSet;
use Generated\Shared\Search\PageIndexMap;
use Generated\Shared\Transfer\ElasticsearchSearchContextTransfer;
use Generated\Shared\Transfer\SearchContextTransfer;
use Generated\Shared\Transfer\SearchDocumentTransfer;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface;
use Spryker\Client\SearchExtension\Dependency\Plugin\ResultFormatterPluginInterface;
use SprykerTest\Client\SearchElasticsearch\Plugin\Fixtures\BaseQueryPlugin;
use SprykerTest\Shared\SearchElasticsearch\Helper\ElasticsearchHelper;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group SearchElasticsearch
 * @group SearchElasticsearchClientTest
 * Add your own group annotations below this line
 */
class SearchElasticsearchClientTest extends Unit
{
    /**
     * @var string
     */
    protected const INDEX_NAME = 'index_name_devtest';

    /**
     * @var \SprykerTest\Client\SearchElasticsearch\SearchElasticsearchClientTester
     */
    protected $tester;

    public function testSearchesBySearchString(): void
    {
        // Arrange
        $documentId = 'document_id';
        $searchString = 'bar';
        $documentData = [
            'foo' => $searchString,
        ];

        $this->tester->haveDocumentInIndex(static::INDEX_NAME, $documentId, $documentData);
        $query = $this->buildQueryStringQuery($searchString);
        $queryPlugin = $this->createQueryPluginMock($query);

        // Act
        $resultSet = $this->tester->getClient()->search($queryPlugin);

        // Assert
        $this->assertMatchFound($resultSet, $searchString);
    }

    protected function createQueryPluginMock(?Query $query = null): QueryInterface
    {
        /** @var \SprykerTest\Client\SearchElasticsearch\Plugin\Fixtures\BaseQueryPlugin|\PHPUnit\Framework\MockObject\MockObject $queryPlugin */
        $queryPlugin = $this->createMock(BaseQueryPlugin::class);

        if ($query) {
            $queryPlugin->method('getSearchQuery')->willReturn($query);
        }

        $searchContextTransfer = $this->buildSearchContextTransfer();
        $queryPlugin->method('getSearchContext')->willReturn($searchContextTransfer);

        return $queryPlugin;
    }

    protected function buildQueryStringQuery(string $searchString): Query
    {
        $query = $this->buildQuery();
        $searchStringQuery = new QueryString($searchString);
        $query->setQuery($searchStringQuery);

        return $query;
    }

    protected function buildQuery(): Query
    {
        return new Query();
    }

    protected function buildBoolQuery(AbstractQuery $matchQuery): BoolQuery
    {
        $boolQuery = new BoolQuery();
        $boolQuery->addMust($matchQuery);

        return $boolQuery;
    }

    protected function buildMultiMatchQuery(string $searchString): MultiMatch
    {
        $fields = [
            PageIndexMap::FULL_TEXT,
            PageIndexMap::FULL_TEXT_BOOSTED . '^' . $this->tester->getConfig()->getFullTextBoostedBoostingValue(),
        ];

        $matchQuery = (new MultiMatch())
            ->setFields($fields)
            ->setQuery($searchString)
            ->setType(MultiMatch::TYPE_CROSS_FIELDS);

        return $matchQuery;
    }

    protected function buildSearchContextTransfer(): SearchContextTransfer
    {
        $searchContextTransfer = new SearchContextTransfer();
        $elasticsearchContext = new ElasticsearchSearchContextTransfer();
        $elasticsearchContext->setIndexName(static::INDEX_NAME);
        $searchContextTransfer->setElasticsearchContext($elasticsearchContext);

        return $searchContextTransfer;
    }

    protected function assertMatchFound(ResultSet $resultSet, string $expectedSearchValue): void
    {
        $matchFound = false;

        foreach ($resultSet->getResults() as $result) {
            $sourceData = $result->getSource();

            if (in_array($expectedSearchValue, $sourceData)) {
                $matchFound = true;

                break;
            }
        }

        $this->assertTrue($matchFound);
    }

    public function testCanWriteDocument(): void
    {
        // Arrange
        $documentId = 'document-id';
        $documentData = ['foo' => 'bar'];
        $searchDocumentTransfer = $this->createSearchDocumentTransfer($documentId, $documentData);

        // Act
        $this->tester->getClient()->writeDocument($searchDocumentTransfer);

        // Assert
        $this->tester->assertDocumentExists($documentId, static::INDEX_NAME);
    }

    public function testCanWriteMultipleDocuments(): void
    {
        // Arrange
        $documentId = 'new-document';
        $documentData = ['foo' => 'bar'];
        $anotherDocumentId = 'another-document';
        $anotherDocumentData = ['bar' => 'baz'];

        $searchDocumentTransfer = $this->createSearchDocumentTransfer($documentId, $documentData);
        $anotherSearchDocumentTransfer = $this->createSearchDocumentTransfer($anotherDocumentId, $anotherDocumentData);

        // Act
        $this->tester->getClient()->writeDocuments([$searchDocumentTransfer, $anotherSearchDocumentTransfer]);

        // Assert
        foreach ([$documentId, $anotherDocumentId] as $currentDocumentId) {
            $this->tester->assertDocumentExists($currentDocumentId, static::INDEX_NAME);
        }
    }

    public function testCanReadDocument(): void
    {
        // Arrange
        $documentId = 'document-id';
        $documentData = ['foo' => 'bar'];
        $this->tester->haveDocumentInIndex(static::INDEX_NAME, $documentId, $documentData);
        $searchDocumentTransfer = $this->createSearchDocumentTransfer($documentId);

        // Act
        $result = $this->tester->getClient()->readDocument($searchDocumentTransfer);

        // Assert
        $this->assertSame($documentData, $result->getData());
    }

    public function testCanDeleteDocument(): void
    {
        // Arrange
        $documentId = 'document-id';
        $this->tester->haveDocumentInIndex(static::INDEX_NAME, $documentId);
        $searchDocumentTransfer = $this->createSearchDocumentTransfer($documentId);

        // Act
        $this->tester->getClient()->deleteDocument($searchDocumentTransfer);

        // Assert
        $this->tester->assertDocumentDoesNotExist($documentId, static::INDEX_NAME);
    }

    public function testCanDeleteMultipleDocuments(): void
    {
        // Arrange
        $documentId = 'document-id';
        $anotherDocumentId = 'another-document-id';

        $searchDocumentTransfer = $this->createSearchDocumentTransfer($documentId);
        $anotherSearchDocumentTransfer = $this->createSearchDocumentTransfer($anotherDocumentId);

        $this->tester->haveDocumentInIndex(static::INDEX_NAME, $documentId);
        $this->tester->haveDocumentInIndex(static::INDEX_NAME, $anotherDocumentId);

        // Act
        $this->tester->getClient()->deleteDocuments([$searchDocumentTransfer, $anotherSearchDocumentTransfer]);

        // Assert
        foreach ([$documentId, $anotherDocumentId] as $id) {
            $this->tester->assertDocumentDoesNotExist($id, static::INDEX_NAME);
        }
    }

    public function testMultiSearchReturnsResultSetsKeyedByInputQueryKey(): void
    {
        // Arrange
        $firstDocumentId = 'document-multisearch-alpha';
        $secondDocumentId = 'document-multisearch-beta';
        $firstSearchValue = 'alphavalue';
        $secondSearchValue = 'betavalue';

        $this->tester->haveDocumentInIndex(static::INDEX_NAME, $firstDocumentId, ['foo' => $firstSearchValue]);
        $this->tester->haveDocumentInIndex(static::INDEX_NAME, $secondDocumentId, ['foo' => $secondSearchValue]);

        $firstQuery = $this->createQueryPluginMock($this->buildQueryStringQuery($firstSearchValue));
        $secondQuery = $this->createQueryPluginMock($this->buildQueryStringQuery($secondSearchValue));

        // Act
        $results = $this->tester->getClient()->multiSearch(
            ['first_key' => $firstQuery, 'second_key' => $secondQuery],
            ['first_key' => [], 'second_key' => []],
        );

        // Assert
        $this->assertArrayHasKey('first_key', $results);
        $this->assertArrayHasKey('second_key', $results);
        $this->assertInstanceOf(ResultSet::class, $results['first_key']);
        $this->assertInstanceOf(ResultSet::class, $results['second_key']);
        $this->assertMatchFound($results['first_key'], $firstSearchValue);
        $this->assertMatchFound($results['second_key'], $secondSearchValue);
    }

    public function testMultiSearchFindsMultipleValuesFromTheSameDocument(): void
    {
        // Arrange
        // Single document with two searchable fields — both queries must return results under separate keys.
        $documentId = 'document-multisearch-shared';
        $firstSearchValue = 'sharedtermone';
        $secondSearchValue = 'sharedtermtwo';

        $this->tester->haveDocumentInIndex(static::INDEX_NAME, $documentId, [
            'field_one' => $firstSearchValue,
            'field_two' => $secondSearchValue,
        ]);

        $firstQuery = $this->createQueryPluginMock($this->buildQueryStringQuery($firstSearchValue));
        $secondQuery = $this->createQueryPluginMock($this->buildQueryStringQuery($secondSearchValue));

        // Act
        $results = $this->tester->getClient()->multiSearch(
            ['query_one' => $firstQuery, 'query_two' => $secondQuery],
            ['query_one' => [], 'query_two' => []],
        );

        // Assert
        // Both queries hit the same document but must be returned under separate keys.
        $this->assertArrayHasKey('query_one', $results);
        $this->assertArrayHasKey('query_two', $results);
        $this->assertInstanceOf(ResultSet::class, $results['query_one']);
        $this->assertInstanceOf(ResultSet::class, $results['query_two']);
        $this->assertMatchFound($results['query_one'], $firstSearchValue);
        $this->assertMatchFound($results['query_two'], $secondSearchValue);
    }

    public function testMultiSearchWithFormattersReturnsFormattedResultsPerKey(): void
    {
        // Arrange
        $documentId = 'document-multisearch-formatted';
        $searchValue = 'formattedvalue';

        $this->tester->haveDocumentInIndex(static::INDEX_NAME, $documentId, ['foo' => $searchValue]);

        $query = $this->createQueryPluginMock($this->buildQueryStringQuery($searchValue));

        $resultFormatter = $this->createMock(ResultFormatterPluginInterface::class);
        $resultFormatter->method('getName')->willReturn('custom');
        $resultFormatter->method('formatResult')->willReturn('formatted');

        // Act
        $results = $this->tester->getClient()->multiSearch(
            ['formatted_key' => $query],
            ['formatted_key' => [$resultFormatter]],
        );

        // Assert
        $this->assertArrayHasKey('formatted_key', $results);
        $this->assertIsArray($results['formatted_key']);
        $this->assertArrayHasKey('custom', $results['formatted_key']);
    }

    /**
     * @return void
     */
    public function testCanCheckConnection()
    {
        $searchConnectionResponseTransfer = $this->tester->getClient()->checkConnection();

        $this->assertTrue($searchConnectionResponseTransfer->getIsSuccessfull());
        $this->assertNotEmpty($searchConnectionResponseTransfer->getRawResponse());
    }

    /**
     * @param string $documentId
     * @param array|string|null $documentData
     * @param string $indexName
     *
     * @return \Generated\Shared\Transfer\SearchDocumentTransfer
     */
    protected function createSearchDocumentTransfer(string $documentId, $documentData = null, string $indexName = self::INDEX_NAME): SearchDocumentTransfer
    {
        $elasticsearchContextTransfer = (new ElasticsearchSearchContextTransfer())->setIndexName($indexName)
            ->setTypeName(ElasticsearchHelper::DEFAULT_MAPPING_TYPE);
        $searchContextTransfer = (new SearchContextTransfer())
            ->setElasticsearchContext($elasticsearchContextTransfer)
            ->setSourceIdentifier(ElasticsearchHelper::DEFAULT_MAPPING_TYPE);
        $searchDocumentTransfer = (new SearchDocumentTransfer())->setId($documentId)
            ->setSearchContext($searchContextTransfer);

        if ($documentData) {
            $searchDocumentTransfer->setData($documentData);
        }

        return $searchDocumentTransfer;
    }
}
