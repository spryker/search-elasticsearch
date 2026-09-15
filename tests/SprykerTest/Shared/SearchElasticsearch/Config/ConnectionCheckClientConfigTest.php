<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\SearchElasticsearch\Config;

use Codeception\Test\Unit;
use Spryker\Shared\SearchElasticsearch\SearchElasticsearchConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Shared
 * @group SearchElasticsearch
 * @group Config
 * @group ConnectionCheckClientConfigTest
 * Add your own group annotations below this line
 */
class ConnectionCheckClientConfigTest extends Unit
{
    /**
     * @var array<string, mixed>
     */
    public const array BASE_CLIENT_CONFIG = [
        'host' => 'search',
        'port' => 9200,
        'transport' => 'Http',
        'timeout' => 90,
        'connectTimeout' => 30,
    ];

    /**
     * A slow search engine must not be able to hold a PHP worker for the Elastica default of 300
     * seconds just because a probe asked whether it is alive.
     */
    public function testConnectionCheckConfigOverridesTheTimeoutsUsedForSearchTraffic(): void
    {
        // Arrange
        $config = $this->createConfig(2, 1);

        // Act
        $connectionCheckClientConfig = $config->getConnectionCheckClientConfig();

        // Assert
        $this->assertSame(2, $connectionCheckClientConfig['timeout']);
        $this->assertSame(1, $connectionCheckClientConfig['connectTimeout']);
    }

    /**
     * The connection check must not change how real search queries behave.
     */
    public function testRegularClientConfigIsLeftUntouched(): void
    {
        // Arrange
        $config = $this->createConfig(2, 1);

        // Act
        $config->getConnectionCheckClientConfig();

        // Assert
        $this->assertSame(90, $config->getClientConfig()['timeout']);
        $this->assertSame(30, $config->getClientConfig()['connectTimeout']);
    }

    public function testConnectionCheckConfigKeepsTheRestOfTheClientConfiguration(): void
    {
        // Arrange
        $config = $this->createConfig(2, 1);

        // Act
        $connectionCheckClientConfig = $config->getConnectionCheckClientConfig();

        // Assert
        $this->assertSame('search', $connectionCheckClientConfig['host']);
        $this->assertSame(9200, $connectionCheckClientConfig['port']);
        $this->assertSame('Http', $connectionCheckClientConfig['transport']);
    }

    /**
     * Zero is the documented escape hatch for projects that want one timeout everywhere.
     */
    public function testZeroInheritsTheTimeoutFromTheRegularClientConfiguration(): void
    {
        // Arrange
        $config = $this->createConfig(0, 0);

        // Act
        $connectionCheckClientConfig = $config->getConnectionCheckClientConfig();

        // Assert
        $this->assertSame(90, $connectionCheckClientConfig['timeout']);
        $this->assertSame(30, $connectionCheckClientConfig['connectTimeout']);
    }

    /**
     * Elastica builds one connection per entry under `connections` / `servers` and only reads the
     * top-level configuration when neither is present. A multi-node project would otherwise keep
     * the 300 second default on the very check this exists to bound.
     */
    public function testTimeoutsReachEveryEntryOfAMultiNodeConfiguration(): void
    {
        // Arrange
        $config = $this->createConfigWithClientConfig([
            'connections' => [
                ['host' => 'search-1', 'port' => 9200],
                ['host' => 'search-2', 'port' => 9200],
            ],
        ], 2, 1);

        // Act
        $connectionCheckClientConfig = $config->getConnectionCheckClientConfig();

        // Assert
        foreach ($connectionCheckClientConfig['connections'] as $connectionConfig) {
            $this->assertSame(2, $connectionConfig['timeout']);
            $this->assertSame(1, $connectionConfig['connectTimeout']);
        }
    }

    public function testTimeoutsReachEveryEntryOfAServersConfiguration(): void
    {
        // Arrange
        $config = $this->createConfigWithClientConfig([
            'servers' => [['host' => 'search-1', 'port' => 9200, 'timeout' => 90]],
        ], 2, 1);

        // Act
        $connectionCheckClientConfig = $config->getConnectionCheckClientConfig();

        // Assert
        $this->assertSame(2, $connectionCheckClientConfig['servers'][0]['timeout']);
        $this->assertSame('search-1', $connectionCheckClientConfig['servers'][0]['host']);
    }

    /**
     * @param array<string, mixed> $clientConfig
     */
    protected function createConfigWithClientConfig(
        array $clientConfig,
        int $timeoutSeconds,
        int $connectTimeoutSeconds
    ): SearchElasticsearchConfig {
        return new class ($clientConfig, $timeoutSeconds, $connectTimeoutSeconds) extends SearchElasticsearchConfig {
            /**
             * @param array<string, mixed> $clientConfig
             */
            public function __construct(
                protected array $clientConfig,
                protected int $timeoutSeconds,
                protected int $connectTimeoutSeconds
            ) {
            }

            /**
             * @return array<string, mixed>
             */
            public function getClientConfig(): array
            {
                return $this->clientConfig;
            }

            public function getConnectionCheckTimeoutSeconds(): int
            {
                return $this->timeoutSeconds;
            }

            public function getConnectionCheckConnectTimeoutSeconds(): int
            {
                return $this->connectTimeoutSeconds;
            }
        };
    }

    protected function createConfig(int $timeoutSeconds, int $connectTimeoutSeconds): SearchElasticsearchConfig
    {
        return new class ($timeoutSeconds, $connectTimeoutSeconds) extends SearchElasticsearchConfig {
            public function __construct(
                protected int $timeoutSeconds,
                protected int $connectTimeoutSeconds
            ) {
            }

            /**
             * @return array<string, mixed>
             */
            public function getClientConfig(): array
            {
                return ConnectionCheckClientConfigTest::BASE_CLIENT_CONFIG;
            }

            public function getConnectionCheckTimeoutSeconds(): int
            {
                return $this->timeoutSeconds;
            }

            public function getConnectionCheckConnectTimeoutSeconds(): int
            {
                return $this->connectTimeoutSeconds;
            }
        };
    }
}
