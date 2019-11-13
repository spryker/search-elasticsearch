<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Reader;

use Elastica\Client;
use Elastica\Document;
use Generated\Shared\Transfer\SearchDocumentTransfer;
use Spryker\Client\SearchElasticsearch\SearchElasticsearchConfig;

class DocumentReader implements DocumentReaderInterface
{
    /**
     * @var \Elastica\Client
     */
    protected $client;

    /**
     * @var \Spryker\Client\SearchElasticsearch\SearchElasticsearchConfig
     */
    protected $config;

    /**
     * @param \Elastica\Client $client
     * @param \Spryker\Client\SearchElasticsearch\SearchElasticsearchConfig $config
     */
    public function __construct(Client $client, SearchElasticsearchConfig $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * @param \Generated\Shared\Transfer\SearchDocumentTransfer $searchDocumentTransfer
     *
     * @return \Generated\Shared\Transfer\SearchDocumentTransfer
     */
    public function readDocument(SearchDocumentTransfer $searchDocumentTransfer): SearchDocumentTransfer
    {
        $indexName = $this->getIndexNameFromSearchDocumentTransfer($searchDocumentTransfer);
        $index = $this->client->getIndex($indexName);

        $document = $index->getType($this->config->getDefaultMappingType())->getDocument($searchDocumentTransfer->getId());

        return $this->mapDocumentToSearchDocumentTransfer($document, $searchDocumentTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\SearchDocumentTransfer $searchDocumentTransfer
     *
     * @return string
     */
    protected function getIndexNameFromSearchDocumentTransfer(SearchDocumentTransfer $searchDocumentTransfer): string
    {
        $this->validateSearchDocumentTransferHasIndexName($searchDocumentTransfer);

        return $searchDocumentTransfer->getSearchContext()->getElasticsearchContext()->getIndexName();
    }

    /**
     * @param \Generated\Shared\Transfer\SearchDocumentTransfer $searchDocumentTransfer
     *
     * @return void
     */
    protected function validateSearchDocumentTransferHasIndexName(SearchDocumentTransfer $searchDocumentTransfer): void
    {
        $searchDocumentTransfer->requireSearchContext()
            ->getSearchContext()
            ->requireElasticsearchContext()
            ->getElasticsearchContext();
    }

    /**
     * @param \Elastica\Document $document
     * @param \Generated\Shared\Transfer\SearchDocumentTransfer $searchDocumentTransfer
     *
     * @return \Generated\Shared\Transfer\SearchDocumentTransfer
     */
    protected function mapDocumentToSearchDocumentTransfer(Document $document, SearchDocumentTransfer $searchDocumentTransfer): SearchDocumentTransfer
    {
        return $searchDocumentTransfer
            ->setId($document->getId())
            ->setData($document->getData());
    }
}
