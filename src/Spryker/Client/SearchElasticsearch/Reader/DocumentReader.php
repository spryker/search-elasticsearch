<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Reader;

use Elastica\Client;
use Elastica\Document;
use Elastica\Index;
use Generated\Shared\Transfer\SearchDocumentTransfer;
use Spryker\Client\SearchElasticsearch\SearchElasticsearchConfig;

class DocumentReader implements DocumentReaderInterface
{
    /**
     * @var \Elastica\Client
     */
    protected $elasticaClient;

    /**
     * @var \Spryker\Client\SearchElasticsearch\SearchElasticsearchConfig
     */
    protected $config;

    /**
     * @param \Elastica\Client $elasticaClient
     * @param \Spryker\Client\SearchElasticsearch\SearchElasticsearchConfig $config
     */
    public function __construct(Client $elasticaClient, SearchElasticsearchConfig $config)
    {
        $this->elasticaClient = $elasticaClient;
        $this->config = $config;
    }

    /**
     * @param \Generated\Shared\Transfer\SearchDocumentTransfer $searchDocumentTransfer
     *
     * @return \Generated\Shared\Transfer\SearchDocumentTransfer
     */
    public function readDocument(SearchDocumentTransfer $searchDocumentTransfer): SearchDocumentTransfer
    {
        $elasticsearchContextTransfer = $searchDocumentTransfer->requireSearchContext()
            ->getSearchContext()
            ->requireElasticsearchContext()
            ->getElasticsearchContext();
        $indexName = $elasticsearchContextTransfer->requireIndexName()->getIndexName();
        $elasticaIndex = $this->elasticaClient->getIndex($indexName);
        $typeName = $elasticsearchContextTransfer->getTypeName() ?? $this->readMappingTypeNameFromElasticsearch($elasticaIndex);

        $elasticaDocument = $elasticaIndex->getType($typeName)->getDocument($searchDocumentTransfer->getId());

        return $this->mapElasticaDocumentToSearchDocumentTransfer($elasticaDocument, $searchDocumentTransfer);
    }

    /**
     * @param \Elastica\Document $document
     * @param \Generated\Shared\Transfer\SearchDocumentTransfer $searchDocumentTransfer
     *
     * @return \Generated\Shared\Transfer\SearchDocumentTransfer
     */
    protected function mapElasticaDocumentToSearchDocumentTransfer(Document $document, SearchDocumentTransfer $searchDocumentTransfer): SearchDocumentTransfer
    {
        return $searchDocumentTransfer
            ->setId($document->getId())
            ->setData($document->getData());
    }

    /**
     * @deprecated Will be removed after the migration to Elasticsearch 7.
     *
     * @param \Elastica\Index $elasticaIndex
     *
     * @return string
     */
    protected function readMappingTypeNameFromElasticsearch(Index $elasticaIndex): string
    {
        return key($elasticaIndex->getMapping());
    }
}
