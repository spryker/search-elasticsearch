<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Config;

use Generated\Shared\Transfer\SearchConfigExtensionTransfer;
use Generated\Shared\Transfer\SearchConfigurationTransfer;

class SearchConfig implements SearchConfigInterface
{
    /**
     * @var \Spryker\Client\SearchElasticsearch\Config\FacetConfigInterface
     */
    protected $facetConfig;

    /**
     * @var \Spryker\Client\SearchElasticsearch\Config\SortConfigInterface
     */
    protected $sortConfig;

    /**
     * @var \Spryker\Client\SearchElasticsearch\Config\PaginationConfigInterface
     */
    protected $paginationConfig;

    /**
     * @var array<\Spryker\Client\SearchExtension\Dependency\Plugin\SearchConfigExpanderPluginInterface>
     */
    protected $configExpanderPlugins;

    /**
     * @param \Spryker\Client\SearchElasticsearch\Config\FacetConfigInterface $facetConfig
     * @param \Spryker\Client\SearchElasticsearch\Config\SortConfigInterface $sortConfig
     * @param \Spryker\Client\SearchElasticsearch\Config\PaginationConfigInterface $paginationConfig
     * @param array<\Spryker\Client\SearchExtension\Dependency\Plugin\SearchConfigBuilderPluginInterface> $configBuilderPlugins
     * @param array<\Spryker\Client\SearchExtension\Dependency\Plugin\SearchConfigExpanderPluginInterface> $searchConfigExpanderPlugins
     */
    public function __construct(
        FacetConfigInterface $facetConfig,
        SortConfigInterface $sortConfig,
        PaginationConfigInterface $paginationConfig,
        array $configBuilderPlugins,
        array $searchConfigExpanderPlugins
    ) {
        $this->facetConfig = $facetConfig;
        $this->sortConfig = $sortConfig;
        $this->paginationConfig = $paginationConfig;
        $this->configExpanderPlugins = $searchConfigExpanderPlugins;

        $this->buildSearchConfig($configBuilderPlugins);
        $this->expandSearchConfig($searchConfigExpanderPlugins);
    }

    public function getFacetConfig(): FacetConfigInterface
    {
        return $this->facetConfig;
    }

    public function getSortConfig(): SortConfigInterface
    {
        return $this->sortConfig;
    }

    public function getPaginationConfig(): PaginationConfigInterface
    {
        return $this->paginationConfig;
    }

    /**
     * @param array<\Spryker\Client\SearchExtension\Dependency\Plugin\SearchConfigBuilderPluginInterface> $searchConfigBuilderPlugins
     *
     * @return void
     */
    protected function buildSearchConfig(array $searchConfigBuilderPlugins): void
    {
        if (!$searchConfigBuilderPlugins) {
            return;
        }

        $searchConfigurationTransfer = new SearchConfigurationTransfer();

        foreach ($searchConfigBuilderPlugins as $searchConfigBuilderPlugin) {
            $searchConfigurationTransfer = $searchConfigBuilderPlugin->buildConfig($searchConfigurationTransfer);
        }

        $this->buildFacetConfig($searchConfigurationTransfer);
        $this->buildSortConfig($searchConfigurationTransfer);
        $this->buildPaginationConfig($searchConfigurationTransfer);
    }

    protected function buildFacetConfig(SearchConfigurationTransfer $searchConfigurationTransfer): void
    {
        foreach ($searchConfigurationTransfer->getFacetConfigItems() as $facetConfigTransfer) {
            $this->facetConfig->addFacet($facetConfigTransfer);
        }
    }

    protected function buildSortConfig(SearchConfigurationTransfer $searchConfigurationTransfer): void
    {
        foreach ($searchConfigurationTransfer->getSortConfigItems() as $sortConfigTransfer) {
            $this->sortConfig->addSort($sortConfigTransfer);
        }
    }

    protected function buildPaginationConfig(SearchConfigurationTransfer $searchConfigurationTransfer): void
    {
        $this->paginationConfig->setPagination(
            $searchConfigurationTransfer->getPaginationConfig(),
        );
    }

    /**
     * @param array<\Spryker\Client\SearchExtension\Dependency\Plugin\SearchConfigExpanderPluginInterface> $configExpanderPlugins
     *
     * @return void
     */
    protected function expandSearchConfig(array $configExpanderPlugins): void
    {
        foreach ($configExpanderPlugins as $configExpanderPlugin) {
            $searchConfigExtensionTransfer = $configExpanderPlugin->getSearchConfigExtension();

            $this->extendFacetConfig($searchConfigExtensionTransfer);
            $this->extendSortConfig($searchConfigExtensionTransfer);
        }
    }

    protected function extendFacetConfig(SearchConfigExtensionTransfer $searchConfigExtensionTransfer): void
    {
        foreach ($searchConfigExtensionTransfer->getFacetConfigs() as $facetConfigTransfer) {
            $this->facetConfig->addFacet($facetConfigTransfer);
        }
    }

    protected function extendSortConfig(SearchConfigExtensionTransfer $searchConfigExtensionTransfer): void
    {
        foreach ($searchConfigExtensionTransfer->getSortConfigs() as $sortConfigTransfer) {
            $this->sortConfig->addSort($sortConfigTransfer);
        }
    }
}
