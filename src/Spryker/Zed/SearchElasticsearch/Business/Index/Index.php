<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SearchElasticsearch\Business\Index;

use Elastica\Client;
use Elastica\Exception\ResponseException;
use Elastica\Index as ElasticaIndex;
use Elastica\Request;
use Generated\Shared\Transfer\ElasticsearchSearchContextTransfer;
use Generated\Shared\Transfer\SearchContextTransfer;
use Spryker\Shared\ErrorHandler\ErrorLogger;
use Spryker\Zed\SearchElasticsearch\Business\SourceIdentifier\SourceIdentifierInterface;
use Spryker\Zed\SearchElasticsearch\Dependency\Facade\SearchElasticsearchToStoreFacadeInterface;
use Spryker\Zed\SearchElasticsearch\SearchElasticsearchConfig;

class Index implements IndexInterface
{
    /**
     * @var \Elastica\Client
     */
    protected $elasticaClient;

    /**
     * @var \Spryker\Zed\SearchElasticsearch\Business\SourceIdentifier\SourceIdentifierInterface
     */
    protected $sourceIdentifier;

    /**
     * @var \Spryker\Zed\SearchElasticsearch\SearchElasticsearchConfig
     */
    protected $config;

    /**
     * @var \Spryker\Zed\SearchElasticsearch\Dependency\Facade\SearchElasticsearchToStoreFacadeInterface
     */
    protected $storeFacade;

    public function __construct(
        Client $elasticaClient,
        SourceIdentifierInterface $sourceIdentifier,
        SearchElasticsearchConfig $config,
        SearchElasticsearchToStoreFacadeInterface $storeFacade
    ) {
        $this->elasticaClient = $elasticaClient;
        $this->sourceIdentifier = $sourceIdentifier;
        $this->config = $config;
        $this->storeFacade = $storeFacade;
    }

    public function openIndex(SearchContextTransfer $searchContextTransfer): bool
    {
        return $this->getIndex($searchContextTransfer)->open()->isOk();
    }

    public function openIndexes(?string $storeName = null): bool
    {
        if (!$storeName) {
            $success = true;
            foreach ($this->storeFacade->getAllStores() as $storeTransfer) {
                $success &= $this->executeOpenIndexes($storeTransfer->getName());
            }

            return (bool)$success;
        }

        return $this->executeOpenIndexes($storeName);
    }

    public function closeIndex(SearchContextTransfer $searchContextTransfer): bool
    {
        return $this->getIndex($searchContextTransfer)->close()->isOk();
    }

    public function closeIndexes(?string $storeName = null): bool
    {
        if (!$storeName) {
            $success = true;

            foreach ($this->storeFacade->getAllStores() as $storeTransfer) {
                $success &= $this->executeCloseIndexes($storeTransfer->getName());
            }

            return (bool)$success;
        }

        return $this->executeCloseIndexes($storeName);
    }

    public function deleteIndex(SearchContextTransfer $searchContextTransfer): bool
    {
        return $this->getIndex($searchContextTransfer)->delete()->isOk();
    }

    public function deleteIndexes(?string $storeName = null): bool
    {
        if (!$storeName) {
            $success = true;
            foreach ($this->storeFacade->getAllStores() as $storeTransfer) {
                $success &= $this->executeDeleteIndexes($storeTransfer->getName());
            }

            return (bool)$success;
        }

        return $this->executeDeleteIndexes($storeName);
    }

    public function copyIndex(SearchContextTransfer $sourceSearchContextTransfer, SearchContextTransfer $targetSearchContextTransfer): bool
    {
        return $this->elasticaClient->request(
            $this->config->getReindexUrl(),
            Request::POST,
            $this->buildCopyCommandRequestData($sourceSearchContextTransfer, $targetSearchContextTransfer),
        )->isOk();
    }

    public function getDocumentsTotalCount(ElasticsearchSearchContextTransfer $elasticsearchSearchContextTransfer): int
    {
        $indexName = $elasticsearchSearchContextTransfer->requireIndexName()->getIndexName();

        try {
            return $this->elasticaClient->getIndex($indexName)->count();
        } catch (ResponseException $e) {
            ErrorLogger::getInstance()->log($e);

            return 0;
        }
    }

    public function getIndexMetaData(ElasticsearchSearchContextTransfer $elasticsearchSearchContextTransfer): array
    {
        $metaData = [];
        $indexName = $elasticsearchSearchContextTransfer->requireIndexName()->getIndexName();

        try {
            $index = $this->elasticaClient->getIndex($indexName);
            $mapping = $index->getMapping()[0] ?? null;
            $metaData = $mapping['_meta'] ?? [];
        } catch (ResponseException $e) {
            // legal catch, if no mapping found (fresh installation etc) we still want to show empty meta data
            ErrorLogger::getInstance()->log($e);
        }

        return $metaData;
    }

    /**
     * @param string|null $storeName
     *
     * @return array<string>
     */
    public function getIndexNames(?string $storeName = null): array
    {
        if ($storeName === null) {
            $result = [];
            foreach ($this->storeFacade->getAllStores() as $storeTransfer) {
                $result = array_merge($result, $this->getAvailableIndexNames($storeTransfer->getName()));
            }

            return $result;
        }

        return $this->getAvailableIndexNames($storeName);
    }

    protected function buildCopyCommandRequestData(
        SearchContextTransfer $sourceSearchContextTransfer,
        SearchContextTransfer $targetSearchContextTransfer
    ): array {
        $sourceIndexName = $this->resolveIndexNameFromSearchContextTransfer($sourceSearchContextTransfer);
        $targetIndexName = $this->resolveIndexNameFromSearchContextTransfer($targetSearchContextTransfer);

        return [
            'source' => [
                'index' => $sourceIndexName,
            ],
            'dest' => [
                'index' => $targetIndexName,
            ],
        ];
    }

    protected function getIndex(SearchContextTransfer $searchContextTransfer): ElasticaIndex
    {
        $indexName = $this->resolveIndexNameFromSearchContextTransfer($searchContextTransfer);

        return $this->elasticaClient->getIndex($indexName);
    }

    protected function getAllIndexes(string $storeName): ?ElasticaIndex
    {
        $availableIndexNamesFormattedString = $this->getAvailableIndexNamesFormattedString($storeName);

        if (!$availableIndexNamesFormattedString) {
            return null;
        }

        return $this->elasticaClient->getIndex($availableIndexNamesFormattedString);
    }

    protected function resolveIndexNameFromSearchContextTransfer(SearchContextTransfer $searchContextTransfer): string
    {
        $this->assertIndexNameIsSet($searchContextTransfer);

        return $searchContextTransfer->getElasticsearchContext()->getIndexName();
    }

    protected function assertIndexNameIsSet(SearchContextTransfer $searchContextTransfer): void
    {
        $searchContextTransfer->requireElasticsearchContext()->getElasticsearchContext()->requireIndexName();
    }

    protected function getAvailableIndexNamesFormattedString(string $storeName): string
    {
        return implode(',', $this->getAvailableIndexNames($storeName));
    }

    /**
     * @param string $storeName
     *
     * @return array<string>
     */
    protected function getAvailableIndexNames(string $storeName): array
    {
        $supportedSourceIdentifiers = $this->config->getSupportedSourceIdentifiers();

        $supportedIndexNames = array_map(function (string $sourceIdentifier) use ($storeName) {
            return $this->sourceIdentifier->translateToIndexName($sourceIdentifier, $storeName);
        }, $supportedSourceIdentifiers);

        return array_intersect($supportedIndexNames, $this->elasticaClient->getCluster()->getIndexNames());
    }

    protected function executeOpenIndexes(string $storeName): bool
    {
        $allIndexes = $this->getAllIndexes($storeName);

        if ($allIndexes) {
            return $allIndexes->open()->isOk();
        }

        return true;
    }

    protected function executeCloseIndexes(string $storeName): bool
    {
        $allIndexes = $this->getAllIndexes($storeName);

        if ($allIndexes) {
            return $allIndexes->close()->isOk();
        }

        return true;
    }

    protected function executeDeleteIndexes(string $storeName): bool
    {
        $allIndexes = $this->getAllIndexes($storeName);

        if ($allIndexes) {
            return $allIndexes->delete()->isOk();
        }

        return true;
    }
}
