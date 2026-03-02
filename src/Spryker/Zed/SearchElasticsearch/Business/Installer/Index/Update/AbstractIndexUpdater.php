<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SearchElasticsearch\Business\Installer\Index\Update;

use Elastica\Client;
use Elastica\Index;
use Generated\Shared\Transfer\IndexDefinitionTransfer;
use Psr\Log\LoggerInterface;
use Spryker\Zed\SearchElasticsearch\Business\Installer\Index\InstallerInterface;
use Spryker\Zed\SearchElasticsearch\Business\Installer\Index\Mapping\MappingBuilderInterface;

abstract class AbstractIndexUpdater implements InstallerInterface
{
    /**
     * @var \Elastica\Client
     */
    protected $client;

    /**
     * @var \Elastica\Index
     */
    protected $index;

    /**
     * @var \Spryker\Zed\SearchElasticsearch\Business\Installer\Index\Mapping\MappingBuilderInterface
     */
    protected $mappingBuilder;

    public function __construct(Client $client, MappingBuilderInterface $mappingBuilder)
    {
        $this->client = $client;
        $this->mappingBuilder = $mappingBuilder;
    }

    public function accept(IndexDefinitionTransfer $indexDefinitionTransfer): bool
    {
        $index = $this->client->getIndex($indexDefinitionTransfer->getIndexName());

        return $index->exists();
    }

    public function run(IndexDefinitionTransfer $indexDefinitionTransfer, LoggerInterface $logger): void
    {
        $index = $this->client->getIndex($indexDefinitionTransfer->getIndexName());
        $logger->info(sprintf('Update mapping for index "%s".', $index->getName()));
        $this->buildMapping($indexDefinitionTransfer->getMappings(), $index);
    }

    abstract protected function buildMapping(array $mappings, Index $index): void;
}
