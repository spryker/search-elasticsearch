<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch;

use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;
use Spryker\Client\SearchElasticsearch\Dependency\Client\SearchElasticsearchToMoneyClientBridge;
use Spryker\Client\SearchElasticsearch\Dependency\Client\SearchElasticsearchToMoneyClientInterface;
use Spryker\Client\SearchElasticsearch\Dependency\Client\SearchElasticsearchToStoreClientBridge;
use Spryker\Client\SearchElasticsearch\Dependency\Client\SearchElasticsearchToStoreClientInterface;
use Spryker\Shared\SearchElasticsearch\Dependency\Client\SearchElasticsearchToLocaleClientBridge;
use Spryker\Shared\SearchElasticsearch\Dependency\Client\SearchElasticsearchToLocaleClientInterface;
use Spryker\Shared\SearchElasticsearch\Dependency\Service\SearchElasticsearchToUtilEncodingServiceBridge;

/**
 * @method \Spryker\Client\SearchElasticsearch\SearchElasticsearchConfig getConfig()
 */
class SearchElasticsearchDependencyProvider extends AbstractDependencyProvider
{
    /**
     * @var string
     */
    public const CLIENT_STORE = 'CLIENT_STORE';

    /**
     * @var string
     */
    public const CLIENT_LOCALE = 'CLIENT_LOCALE';

    /**
     * @var string
     */
    public const CLIENT_MONEY = 'CLIENT_MONEY';

    /**
     * @var string
     */
    public const PLUGINS_SEARCH_CONFIG_EXPANDER = 'PLUGINS_SEARCH_CONFIG_EXPANDER';

    /**
     * @var string
     */
    public const PLUGINS_SEARCH_CONFIG_BUILDER = 'PLUGINS_SEARCH_CONFIG_BUILDER';

    /**
     * @var string
     */
    public const SERVICE_UTIL_ENCODING = 'SERVICE_UTIL_ENCODING';

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return \Spryker\Client\Kernel\Container
     */
    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = $this->addStoreClient($container);
        $container = $this->addLocaleClient($container);
        $container = $this->addSearchConfigBuilderPlugins($container);
        $container = $this->addSearchConfigExpanderPlugins($container);
        $container = $this->addMoneyClient($container);
        $container = $this->addUtilEncodingService($container);

        return $container;
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return \Spryker\Client\Kernel\Container
     */
    protected function addSearchConfigBuilderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_SEARCH_CONFIG_BUILDER, function (Container $container): array {
            return $this->getSearchConfigBuilderPlugins($container);
        });

        return $container;
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return array<\Spryker\Client\SearchExtension\Dependency\Plugin\SearchConfigBuilderPluginInterface>
     */
    protected function getSearchConfigBuilderPlugins(Container $container): array
    {
        return [];
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return \Spryker\Client\Kernel\Container
     */
    protected function addStoreClient(Container $container): Container
    {
        $container->set(static::CLIENT_STORE, function (Container $container): SearchElasticsearchToStoreClientInterface {
            return new SearchElasticsearchToStoreClientBridge(
                $container->getLocator()->store()->client(),
            );
        });

        return $container;
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return \Spryker\Client\Kernel\Container
     */
    protected function addLocaleClient(Container $container): Container
    {
        $container->set(static::CLIENT_LOCALE, function (Container $container): SearchElasticsearchToLocaleClientInterface {
            return new SearchElasticsearchToLocaleClientBridge(
                $container->getLocator()->locale()->client(),
            );
        });

        return $container;
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return \Spryker\Client\Kernel\Container
     */
    protected function addSearchConfigExpanderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_SEARCH_CONFIG_EXPANDER, function (Container $container): array {
            return $this->getSearchConfigExpanderPlugins($container);
        });

        return $container;
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return array<\Spryker\Client\SearchExtension\Dependency\Plugin\SearchConfigExpanderPluginInterface>
     */
    protected function getSearchConfigExpanderPlugins(Container $container): array
    {
        return [];
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return \Spryker\Client\Kernel\Container
     */
    protected function addMoneyClient(Container $container): Container
    {
        $container->set(static::CLIENT_MONEY, function (Container $container): SearchElasticsearchToMoneyClientInterface {
            return new SearchElasticsearchToMoneyClientBridge(
                $container->getLocator()->money()->client(),
            );
        });

        return $container;
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return \Spryker\Client\Kernel\Container
     */
    protected function addUtilEncodingService(Container $container): Container
    {
        $container->set(static::SERVICE_UTIL_ENCODING, function (Container $container) {
            return new SearchElasticsearchToUtilEncodingServiceBridge(
                $container->getLocator()->utilEncoding()->service(),
            );
        });

        return $container;
    }
}
