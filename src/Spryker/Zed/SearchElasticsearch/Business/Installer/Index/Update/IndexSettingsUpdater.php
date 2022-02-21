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
use Spryker\Zed\SearchElasticsearch\Business\Exception\MissingIndexStateException;
use Spryker\Zed\SearchElasticsearch\Business\Installer\Index\InstallerInterface;
use Spryker\Zed\SearchElasticsearch\Dependency\Service\SearchElasticsearchToUtilSanitizeServiceInterface;
use Spryker\Zed\SearchElasticsearch\SearchElasticsearchConfig;

class IndexSettingsUpdater implements InstallerInterface
{
    /**
     * @phpstan-var non-empty-string
     *
     * @var string
     */
    protected const SETTING_PATH_DELIMITER = '.';

    /**
     * @var \Elastica\Client
     */
    protected $client;

    /**
     * @var \Spryker\Zed\SearchElasticsearch\SearchElasticsearchConfig
     */
    protected $config;

    /**
     * @var \Spryker\Zed\SearchElasticsearch\Dependency\Service\SearchElasticsearchToUtilSanitizeServiceInterface
     */
    protected $utilSanitizeService;

    /**
     * @param \Elastica\Client $client
     * @param \Spryker\Zed\SearchElasticsearch\SearchElasticsearchConfig $config
     * @param \Spryker\Zed\SearchElasticsearch\Dependency\Service\SearchElasticsearchToUtilSanitizeServiceInterface $utilSanitizeService
     */
    public function __construct(Client $client, SearchElasticsearchConfig $config, SearchElasticsearchToUtilSanitizeServiceInterface $utilSanitizeService)
    {
        $this->client = $client;
        $this->config = $config;
        $this->utilSanitizeService = $utilSanitizeService;
    }

    /**
     * @param \Generated\Shared\Transfer\IndexDefinitionTransfer $indexDefinitionTransfer
     *
     * @return bool
     */
    public function accept(IndexDefinitionTransfer $indexDefinitionTransfer): bool
    {
        $index = $this->client->getIndex($indexDefinitionTransfer->getIndexName());

        return $index->exists() && $indexDefinitionTransfer->getSettings();
    }

    /**
     * @param \Generated\Shared\Transfer\IndexDefinitionTransfer $indexDefinitionTransfer
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return void
     */
    public function run(IndexDefinitionTransfer $indexDefinitionTransfer, LoggerInterface $logger): void
    {
        $index = $this->client->getIndex($indexDefinitionTransfer->getIndexName());
        $settings = $this->getSettings($indexDefinitionTransfer, $index, $logger);

        if ($this->isSettingsForUpdateExists($settings)) {
            $index->setSettings($settings);
        }
    }

    /**
     * @param \Generated\Shared\Transfer\IndexDefinitionTransfer $indexDefinitionTransfer
     * @param \Elastica\Index $index
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return array<string>
     */
    protected function getSettings(IndexDefinitionTransfer $indexDefinitionTransfer, Index $index, LoggerInterface $logger): array
    {
        $indexState = $this->getIndexState($index);
        $settings = $indexDefinitionTransfer->getSettings();

        $settings = $this->filterSettingsByIndexState($indexState, $settings, $logger);
        $settings = $this->removeBlacklistedSettings($settings);
        $settings = $this->filterEmptySettings($settings);

        return $settings;
    }

    /**
     * @param \Elastica\Index $index
     *
     * @throws \Spryker\Zed\SearchElasticsearch\Business\Exception\MissingIndexStateException
     *
     * @return string
     */
    protected function getIndexState(Index $index): string
    {
        $clusterState = $index->getClient()->getCluster()->getState();

        if (isset($clusterState['metadata']['indices'][$index->getName()]['state'])) {
            return $clusterState['metadata']['indices'][$index->getName()]['state'];
        }

        throw new MissingIndexStateException(sprintf('Can not determine state for index "%s".', $index->getName()));
    }

    /**
     * @param string $indexState
     * @param array<string> $settings
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return array<string>
     */
    protected function filterSettingsByIndexState(string $indexState, array $settings, LoggerInterface $logger): array
    {
        $notUpdatableIndexSettings = [];

        if ($indexState === SearchElasticsearchConfig::INDEX_OPEN_STATE) {
            $notUpdatableIndexSettings = $this->config->getStaticIndexSettings();
            $logger->info('Index is open, updating dynamic settings.');
        }

        if ($indexState === SearchElasticsearchConfig::INDEX_CLOSE_STATE) {
            $notUpdatableIndexSettings = $this->config->getDynamicIndexSettings();
            $logger->info('Index is closed, updating static settings.');
        }

        foreach ($notUpdatableIndexSettings as $notUpdatableIndexSettingPath) {
            $settings = $this->removeSettingPath($settings, $notUpdatableIndexSettingPath);
        }

        return $settings;
    }

    /**
     * @param array $settings
     * @param string $removeSettingPath
     *
     * @return array
     */
    protected function removeSettingPath(array $settings, string $removeSettingPath): array
    {
        $settingsElement = &$settings;
        $settingPathArray = explode(static::SETTING_PATH_DELIMITER, $removeSettingPath);
        $lastPathNumber = $this->getLastPathNumber($settingPathArray);

        foreach ($settingPathArray as $pathNumber => $settingElementKey) {
            if (!isset($settingsElement[$settingElementKey])) {
                return $settings;
            }

            if ($pathNumber === $lastPathNumber) {
                unset($settingsElement[$settingElementKey]);

                return $settings;
            }
            $settingsElement = &$settingsElement[$settingElementKey];
        }

        return $settings;
    }

    /**
     * @param array $settingPathArray
     *
     * @return int
     */
    protected function getLastPathNumber(array $settingPathArray): int
    {
        end($settingPathArray);

        /** @phpstan-var int */
        return key($settingPathArray);
    }

    /**
     * @param array $settings
     *
     * @return array
     */
    protected function removeBlacklistedSettings(array $settings): array
    {
        $blacklistSettingsForIndexUpdate = $this->config->getBlacklistSettingsForIndexUpdate();

        foreach ($blacklistSettingsForIndexUpdate as $blacklistedSettingPath) {
            $settings = $this->removeSettingPath($settings, $blacklistedSettingPath);
        }

        return $settings;
    }

    /**
     * @param array<string> $settings
     *
     * @return bool
     */
    protected function isSettingsForUpdateExists(array $settings): bool
    {
        $settings = array_filter($settings, function ($setting) {
            return (bool)$setting;
        });

        return (bool)$settings;
    }

    /**
     * @param array $settings
     *
     * @return array
     */
    protected function filterEmptySettings(array $settings): array
    {
        return $this->utilSanitizeService->filterOutBlankValuesRecursively($settings);
    }
}
