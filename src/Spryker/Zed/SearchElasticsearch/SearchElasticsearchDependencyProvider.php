<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SearchElasticsearch;

use GuzzleHttp\Client;
use Spryker\Shared\SearchElasticsearch\Dependency\Client\SearchElasticsearchToStoreClientBridge;
use Spryker\Shared\SearchElasticsearch\Dependency\Client\SearchElasticsearchToStoreClientInterface;
use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\SearchElasticsearch\Dependency\Guzzle\SearchElasticsearchToGuzzleClientAdapter;
use Spryker\Zed\SearchElasticsearch\Dependency\Guzzle\SearchElasticsearchToGuzzleClientInterface;
use Spryker\Zed\SearchElasticsearch\Dependency\Service\SearchToUtilEncodingBridge;

/**
 * @method \Spryker\Zed\SearchElasticsearch\SearchElasticsearchConfig getConfig()
 */
class SearchElasticsearchDependencyProvider extends AbstractBundleDependencyProvider
{
    public const CLIENT_STORE = 'CLIENT_STORE';
    public const CLIENT_SEARCH = 'CLIENT_SEARCH';
    public const CLIENT_GUZZLE = 'CLIENT_GUZZLE';
    public const SERVICE_UTIL_ENCODING = 'SERVICE_UTIL_ENCODING';

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = $this->addSearchClient($container);
        $container = $this->addGuzzleClient($container);
        $container = $this->addUtilEncodingFacade($container);
        $container = $this->addStoreClient($container);

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addSearchClient(Container $container): Container
    {
        $container->set(static::CLIENT_SEARCH, function (Container $container) {
            return $container->getLocator()->searchElasticsearch()->client();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addUtilEncodingFacade(Container $container): Container
    {
        $container->set(static::SERVICE_UTIL_ENCODING, function (Container $container) {
            return new SearchToUtilEncodingBridge($container->getLocator()->utilEncoding()->service());
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addStoreClient(Container $container): Container
    {
        $container->set(static::CLIENT_STORE, function (Container $container): SearchElasticsearchToStoreClientInterface {
            return new SearchElasticsearchToStoreClientBridge(
                $container->getLocator()->store()->client()
            );
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addGuzzleClient(Container $container): Container
    {
        $container->set(static::CLIENT_GUZZLE, function (Container $container): SearchElasticsearchToGuzzleClientInterface {
            return new SearchElasticsearchToGuzzleClientAdapter(
                new Client()
            );
        });

        return $container;
    }
}
