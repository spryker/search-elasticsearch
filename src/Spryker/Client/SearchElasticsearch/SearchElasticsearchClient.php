<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch;

use Generated\Shared\Transfer\SearchConnectionResponseTransfer;
use Generated\Shared\Transfer\SearchDocumentTransfer;
use Spryker\Client\Kernel\AbstractClient;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface;

/**
 * @method \Spryker\Client\SearchElasticsearch\SearchElasticsearchFactory getFactory()
 */
class SearchElasticsearchClient extends AbstractClient implements SearchElasticsearchClientInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface $searchQuery
     * @param array<\Spryker\Client\SearchExtension\Dependency\Plugin\ResultFormatterPluginInterface> $resultFormatters
     * @param array<string, mixed> $requestParameters
     *
     * @return \Elastica\ResultSet|array
     */
    public function search(QueryInterface $searchQuery, array $resultFormatters = [], array $requestParameters = [])
    {
        return $this->getFactory()->createSearch()->search($searchQuery, $resultFormatters, $requestParameters);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\SearchDocumentTransfer $searchDocumentTransfer
     *
     * @return \Generated\Shared\Transfer\SearchDocumentTransfer
     */
    public function readDocument(SearchDocumentTransfer $searchDocumentTransfer): SearchDocumentTransfer
    {
        return $this->getFactory()->createDocumentReader()->readDocument($searchDocumentTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\SearchDocumentTransfer $searchDocumentTransfer
     *
     * @return bool
     */
    public function writeDocument(SearchDocumentTransfer $searchDocumentTransfer): bool
    {
        return $this->getFactory()->createDocumentWriter()->writeDocument($searchDocumentTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\SearchDocumentTransfer> $searchDocumentTransfers
     *
     * @return bool
     */
    public function writeDocuments(array $searchDocumentTransfers): bool
    {
        return $this->getFactory()->createDocumentWriter()->writeDocuments($searchDocumentTransfers);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\SearchDocumentTransfer $searchDocumentTransfer
     *
     * @return bool
     */
    public function deleteDocument(SearchDocumentTransfer $searchDocumentTransfer): bool
    {
        return $this->getFactory()->createDocumentWriter()->deleteDocument($searchDocumentTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\SearchDocumentTransfer> $searchDocumentTransfers
     *
     * @return bool
     */
    public function deleteDocuments(array $searchDocumentTransfers): bool
    {
        return $this->getFactory()->createDocumentWriter()->deleteDocuments($searchDocumentTransfers);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return \Generated\Shared\Transfer\SearchConnectionResponseTransfer
     */
    public function checkConnection(): SearchConnectionResponseTransfer
    {
        return $this->getFactory()->createConnection()->checkConnection();
    }
}
