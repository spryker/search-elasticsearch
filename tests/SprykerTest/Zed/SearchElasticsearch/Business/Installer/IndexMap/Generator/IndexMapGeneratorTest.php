<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\SearchElasticsearch\Business\Installer\IndexMap\Generator;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\IndexDefinitionTransfer;
use Spryker\Zed\SearchElasticsearch\Business\Installer\IndexMap\Cleaner\IndexMapCleaner;
use Spryker\Zed\SearchElasticsearch\Business\Installer\IndexMap\Generator\IndexMapGenerator;
use Spryker\Zed\SearchElasticsearch\SearchElasticsearchConfig;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group SearchElasticsearch
 * @group Business
 * @group Installer
 * @group IndexMap
 * @group Generator
 * @group IndexMapGeneratorTest
 * Add your own group annotations below this line
 */
class IndexMapGeneratorTest extends Unit
{
    protected const string TARGET_DIRECTORY = __DIR__ . '/Generated/';

    protected const string TEST_FILES_DIRECTORY = __DIR__ . '/test_files/';

    public function tearDown(): void
    {
        $config = $this->createMock(SearchElasticsearchConfig::class);
        $config->method('getClassTargetDirectory')->willReturn(static::TARGET_DIRECTORY);

        $cleaner = new IndexMapCleaner($config);
        $cleaner->cleanDirectory();
    }

    public function testGenerateMultiFieldIndexMap(): void
    {
        $generator = $this->createIndexMapGenerator();

        $indexDefinitionTransfer = $this->createIndexDefinitionTransfer('multi-field', [
            'full-text' => [
                'search_analyzer' => 'ja_kuromoji_search_analyzer',
                'analyzer' => 'ja_kuromoji_index_analyzer',
                'fields' => [
                    'ngram' => [
                        'type' => 'text',
                        'search_analyzer' => 'ja_ngram_search_analyzer',
                        'analyzer' => 'ja_ngram_index_analyzer',
                    ],
                ],
            ],
            'full-text-boosted' => [
                'search_analyzer' => 'ja_kuromoji_search_analyzer',
                'analyzer' => 'ja_kuromoji_index_analyzer',
                'fields' => [
                    'ngram' => [
                        'type' => 'text',
                        'search_analyzer' => 'ja_ngram_search_analyzer',
                        'analyzer' => 'ja_ngram_index_analyzer',
                    ],
                ],
            ],
        ]);

        $generator->generate($indexDefinitionTransfer);

        $this->assertFileEquals(
            static::TEST_FILES_DIRECTORY . 'MultiFieldIndexMap.expected.php',
            static::TARGET_DIRECTORY . 'MultiFieldIndexMap.php',
        );
    }

    protected function createIndexMapGenerator(): IndexMapGenerator
    {
        $config = $this->createMock(SearchElasticsearchConfig::class);
        $config->method('getClassTargetDirectory')->willReturn(static::TARGET_DIRECTORY);
        $config->method('getPermissionMode')->willReturn(0777);

        $realConfig = new SearchElasticsearchConfig();
        $twig = new Environment(
            new FilesystemLoader($realConfig->getIndexMapClassTemplateDirectory()),
        );

        return new IndexMapGenerator($config, $twig);
    }

    protected function createIndexDefinitionTransfer(string $mappingName, array $properties): IndexDefinitionTransfer
    {
        $indexDefinitionTransfer = new IndexDefinitionTransfer();
        $indexDefinitionTransfer->setMappings([
            $mappingName => [
                'properties' => $properties,
            ],
        ]);

        return $indexDefinitionTransfer;
    }
}
