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

class DocumentReader implements DocumentReaderInterface
{
    /**
     * @var \Elastica\Client
     */
    protected $elasticaClient;

    public function __construct(Client $elasticaClient)
    {
        $this->elasticaClient = $elasticaClient;
    }

    public function readDocument(SearchDocumentTransfer $searchDocumentTransfer): SearchDocumentTransfer
    {
        $elasticaDocument = $this->elasticaClient
            ->getIndex(
                $this->getIndexName($searchDocumentTransfer),
            )
            ->getDocument(
                $searchDocumentTransfer->getId(),
            );

        return $this->mapElasticaDocumentToSearchDocumentTransfer($elasticaDocument, $searchDocumentTransfer);
    }

    protected function mapElasticaDocumentToSearchDocumentTransfer(Document $document, SearchDocumentTransfer $searchDocumentTransfer): SearchDocumentTransfer
    {
        /** @var array $data */
        $data = $document->getData();
        /** @var string $id */
        $id = $document->getId();

        return $searchDocumentTransfer
            ->setId($id)
            ->setData($data);
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
        /** @phpstan-var string */
        return key($elasticaIndex->getMapping());
    }

    protected function getIndexName(SearchDocumentTransfer $searchDocumentTransfer): string
    {
        return $searchDocumentTransfer->requireSearchContext()
            ->getSearchContext()
            ->requireElasticsearchContext()
            ->getElasticsearchContext()
            ->requireIndexName()
            ->getIndexName();
    }
}
