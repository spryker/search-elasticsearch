<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SearchElasticsearch\Business\Installer\IndexMap\Generator;

use Generated\Shared\Transfer\IndexDefinitionTransfer;
use Laminas\Filter\Word\UnderscoreToCamelCase;
use Spryker\Zed\SearchElasticsearch\SearchElasticsearchConfig;
use Twig\Environment;

class IndexMapGenerator implements IndexMapGeneratorInterface
{
    /**
     * @var string
     */
    public const CLASS_NAME_SUFFIX = 'IndexMap';

    /**
     * @var string
     */
    public const CLASS_EXTENSION = '.php';

    /**
     * @var string
     */
    public const PROPERTIES = 'properties';

    /**
     * @var string
     */
    public const PROPERTY_PATH_SEPARATOR = '.';

    /**
     * @var string
     */
    public const TEMPLATE_VARIABLE_CLASS_NAME = 'className';

    /**
     * @var string
     */
    public const TEMPLATE_VARIABLE_CONSTANTS = 'constants';

    /**
     * @var string
     */
    public const TEMPLATE_VARIABLE_METADATA = 'metadata';

    /**
     * @var \Spryker\Zed\SearchElasticsearch\SearchElasticsearchConfig
     */
    protected $config;

    /**
     * @var \Twig\Environment
     */
    protected $twig;

    public function __construct(SearchElasticsearchConfig $config, Environment $twig)
    {
        $this->config = $config;
        $this->twig = $twig;
    }

    public function generate(IndexDefinitionTransfer $indexDefinition): void
    {
        foreach ($indexDefinition->getMappings() as $mappingName => $mapping) {
            $mappingName = $this->normalizeToClassName($mappingName);
            $this->generateIndexMapClass($mappingName, $mapping);
        }
    }

    protected function normalizeToClassName(string $mappingName): string
    {
        $normalized = preg_replace('/\\W+/', '_', $mappingName);
        $normalized = trim($normalized, '_');

        $filter = new UnderscoreToCamelCase();
        /** @var string $normalized */
        $normalized = $filter->filter($normalized);
        $normalized = ucfirst($normalized);

        return $normalized;
    }

    protected function generateIndexMapClass(string $mappingName, array $mapping): void
    {
        $targetDirectory = $this->config->getClassTargetDirectory();
        $fileName = $mappingName . static::CLASS_NAME_SUFFIX . static::CLASS_EXTENSION;
        $templateData = $this->getTemplateData($mappingName, $mapping);
        $fileContent = $this->twig->render('class.php.twig', $templateData);

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, $this->config->getPermissionMode(), true);
        }

        file_put_contents($targetDirectory . $fileName, $fileContent);
    }

    protected function getTemplateData(string $mappingName, array $mapping): array
    {
        $properties = $this->getMappingProperties($mapping);

        return [
            static::TEMPLATE_VARIABLE_CLASS_NAME => $mappingName . static::CLASS_NAME_SUFFIX,
            static::TEMPLATE_VARIABLE_CONSTANTS => $this->getConstants($properties),
            static::TEMPLATE_VARIABLE_METADATA => $this->getMetadata($properties),
        ];
    }

    protected function getConstants(array $properties, ?string $path = null): array
    {
        $constants = [];

        foreach ($properties as $propertyName => $propertyData) {
            $propertyConstantName = $this->convertToConstant($path . $propertyName);

            $constants[$propertyConstantName] = $path . $propertyName;

            $constants = $this->getChildConstants($path, $propertyData, $propertyName, $constants);
        }

        return $constants;
    }

    protected function getMetadata(array $properties, ?string $path = null): array
    {
        $metadata = [];

        foreach ($properties as $propertyName => $propertyData) {
            $propertyConstantName = $this->convertToConstant($path . $propertyName);

            $metadata = $this->getScalarMetadata($propertyData, $metadata, $propertyConstantName);

            $metadata = $this->getChildMetadata($path, $propertyData, $propertyName, $metadata);
        }

        return $metadata;
    }

    protected function getMappingProperties(array $mapping): array
    {
        return $mapping[static::PROPERTIES] ?? [];
    }

    protected function convertToConstant(string $string): string
    {
        $normalized = preg_replace('/\\W+/', '_', $string);
        $normalized = trim($normalized, '_');
        $normalized = mb_strtoupper($normalized);

        return $normalized;
    }

    protected function getScalarMetadata(array $propertyData, array $metadata, string $propertyConstantName): array
    {
        foreach ($propertyData as $key => $value) {
            if (is_scalar($value)) {
                $metadata[$propertyConstantName][$key] = $value;
            }
        }

        return $metadata;
    }

    protected function getChildMetadata(?string $path, array $propertyData, string $propertyName, array $metadata): array
    {
        if (!isset($propertyData[static::PROPERTIES])) {
            return $metadata;
        }

        $path .= $propertyName . static::PROPERTY_PATH_SEPARATOR;

        $childMetadata = $this->getMetadata($propertyData[static::PROPERTIES], $path);

        $metadata = array_merge($metadata, $childMetadata);

        return $metadata;
    }

    protected function getChildConstants(?string $path, array $propertyData, string $propertyName, array $constants): array
    {
        if (!isset($propertyData[static::PROPERTIES])) {
            return $constants;
        }

        $path .= $propertyName . static::PROPERTY_PATH_SEPARATOR;

        $childMetadata = $this->getConstants($propertyData[static::PROPERTIES], $path);

        $constants = array_merge($constants, $childMetadata);

        return $constants;
    }
}
