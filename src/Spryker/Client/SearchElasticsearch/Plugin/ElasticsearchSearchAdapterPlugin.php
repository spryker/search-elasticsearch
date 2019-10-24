<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Plugin;

use Generated\Shared\Transfer\SearchContextTransfer;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface;
use Spryker\Client\SearchExtension\Dependency\Plugin\SearchAdapterPluginInterface;

/**
 * @method \Spryker\Client\SearchElasticsearch\SearchElasticsearchClientInterface getClient()
 * @method \Spryker\Client\SearchElasticsearch\SearchElasticsearchFactory getFactory()()
 */
class ElasticsearchSearchAdapterPlugin extends AbstractPlugin implements SearchAdapterPluginInterface
{
    /**
     * {@inheritDoc}
     * - Performs search in Elasticsearch.
     *
     * @api
     *
     * @param \Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface $searchQuery
     * @param \Spryker\Client\SearchExtension\Dependency\Plugin\ResultFormatterPluginInterface[] $resultFormatters
     * @param array $requestParameters
     *
     * @return array|\Elastica\ResultSet
     */
    public function search(QueryInterface $searchQuery, array $resultFormatters = [], array $requestParameters = [])
    {
        return $this->getClient()->search($searchQuery, $resultFormatters, $requestParameters);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string|null $indexName
     *
     * @return int
     */
    public function getTotalCount(?string $indexName = null): int
    {
        return $this->getClient()->getTotalCount($indexName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string|null $indexName
     *
     * @return array
     */
    public function getMetaData(?string $indexName = null): array
    {
        return $this->getClient()->getMetaData($indexName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $key
     * @param string $indexName
     *
     * @return mixed
     */
    public function read(string $key, string $indexName)
    {
        // TODO add real return value
        return 'foo';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string|null $indexName
     *
     * @return bool
     */
    public function delete(?string $indexName = null): bool
    {
        return $this->getClient()->delete($indexName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array $searchDocumentTransfers
     *
     * @return bool
     */
    public function deleteDocuments(array $searchDocumentTransfers): bool
    {
        // TODO add real return value
        return false;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\SearchContextTransfer $searchContextTransfer
     *
     * @return bool
     */
    public function isApplicable(SearchContextTransfer $searchContextTransfer): bool
    {
        return $this->getFactory()->createSourceIdentifierChecker()->isSupported($searchContextTransfer);
    }
}
