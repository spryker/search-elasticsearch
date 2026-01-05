<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SearchElasticsearch;

use Spryker\Glue\Kernel\AbstractFactory;
use Spryker\Glue\SearchElasticsearch\WebProfiler\DataCollector\ElasticsearchDataCollector;
use Spryker\Shared\SearchElasticsearch\Dependency\Service\SearchElasticsearchToUtilEncodingServiceInterface;
use Spryker\Shared\SearchElasticsearch\Logger\ElasticsearchInMemoryLogger;
use Spryker\Shared\SearchElasticsearch\Logger\ElasticsearchLoggerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * @method \Spryker\Glue\SearchElasticsearch\SearchElasticsearchConfig getConfig()
 */
class SearchElasticsearchFactory extends AbstractFactory
{
    /**
     * @return \Symfony\Component\HttpKernel\DataCollector\DataCollector
     */
    public function createSearchDataCollector(): DataCollector
    {
        return new ElasticsearchDataCollector(
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
     * @return \Spryker\Shared\SearchElasticsearch\Dependency\Service\SearchElasticsearchToUtilEncodingServiceInterface
     */
    public function getUtilEncodingService(): SearchElasticsearchToUtilEncodingServiceInterface
    {
        return $this->getProvidedDependency(SearchElasticsearchDependencyProvider::SERVICE_UTIL_ENCODING);
    }
}
