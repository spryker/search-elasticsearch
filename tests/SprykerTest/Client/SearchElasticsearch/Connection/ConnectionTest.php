<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\SearchElasticsearch\Connection;

use Codeception\Test\Unit;
use Elastica\Client;
use Elastica\Request;
use Elastica\Response;
use Exception;
use Spryker\Client\SearchElasticsearch\Connection\Connection;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group SearchElasticsearch
 * @group Connection
 * @group ConnectionTest
 * Add your own group annotations below this line
 */
class ConnectionTest extends Unit
{
    /**
     * @var array<string, mixed>
     */
    protected const array CLUSTER_HEALTH_RESPONSE = [
        'cluster_name' => 'docker-cluster',
        'status' => 'green',
        'timed_out' => false,
    ];

    /**
     * @var array<string, mixed>
     */
    protected array $capturedRequest = [];

    public function testCheckConnectionQueriesTheClusterHealthEndpoint(): void
    {
        // Arrange
        $clientMock = $this->createClientMock();

        // Act
        (new Connection($clientMock))->checkConnection();

        // Assert
        $this->assertSame('_cluster/health', $this->capturedRequest['path']);
        $this->assertSame(Request::GET, $this->capturedRequest['method']);
    }

    /**
     * The `_stats` endpoint this check previously used returns a per-index, per-shard document
     * whose size grows with the cluster. `level=shards` would reintroduce exactly that growth,
     * so it must not be requested.
     */
    public function testCheckConnectionDoesNotRequestPerShardDetail(): void
    {
        // Arrange
        $clientMock = $this->createClientMock();

        // Act
        (new Connection($clientMock))->checkConnection();

        // Assert
        $this->assertArrayNotHasKey(
            'level',
            $this->capturedRequest['query'],
            'Expected no `level` parameter, which would make the response scale with the cluster.',
        );
        $this->assertStringNotContainsString('_stats', $this->capturedRequest['path']);
    }

    public function testCheckConnectionAsksTheLocalNodeToAvoidDependingOnTheMaster(): void
    {
        // Arrange
        $clientMock = $this->createClientMock();

        // Act
        (new Connection($clientMock))->checkConnection();

        // Assert
        $this->assertSame('true', $this->capturedRequest['query']['local'] ?? null);
    }

    public function testCheckConnectionReturnsSuccessfulResponseWithTheClusterHealthPayload(): void
    {
        // Arrange
        $clientMock = $this->createClientMock();

        // Act
        $searchConnectionResponseTransfer = (new Connection($clientMock))->checkConnection();

        // Assert
        $this->assertTrue($searchConnectionResponseTransfer->getIsSuccessfull());
        $this->assertSame(static::CLUSTER_HEALTH_RESPONSE, $searchConnectionResponseTransfer->getRawResponse());
    }

    public function testCheckConnectionPropagatesTransportFailures(): void
    {
        // Arrange
        $clientMock = $this->createMock(Client::class);
        $clientMock->method('request')->willThrowException(new Exception('no route to host'));

        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('no route to host');

        // Act
        (new Connection($clientMock))->checkConnection();
    }

    protected function createClientMock(): Client
    {
        $responseMock = $this->createMock(Response::class);
        $responseMock->method('getData')->willReturn(static::CLUSTER_HEALTH_RESPONSE);

        $clientMock = $this->createMock(Client::class);
        $clientMock
            ->method('request')
            ->willReturnCallback(
                function (string $path, string $method, $data, array $query) use ($responseMock): Response {
                    $this->capturedRequest = [
                        'path' => $path,
                        'method' => $method,
                        'data' => $data,
                        'query' => $query,
                    ];

                    return $responseMock;
                },
            );

        return $clientMock;
    }
}
