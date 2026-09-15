<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Connection;

use Elastica\Client;
use Elastica\Request;
use Generated\Shared\Transfer\SearchConnectionResponseTransfer;

class Connection implements ConnectionInterface
{
    /**
     * Served from the local node's cluster state, so the check does not depend on the master
     * being responsive. `level` is deliberately left at its default: `level=shards` would return
     * a per-index, per-shard document whose size grows with the cluster, which is the cost this
     * check exists to avoid.
     *
     * @var array<string, string>
     */
    protected const array CLUSTER_HEALTH_PARAMS = [
        'local' => 'true',
    ];

    protected const string CLUSTER_HEALTH_PATH = '_cluster/health';

    /**
     * @var \Elastica\Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function checkConnection(): SearchConnectionResponseTransfer
    {
        $clusterHealthData = $this->client
            ->request(static::CLUSTER_HEALTH_PATH, Request::GET, [], static::CLUSTER_HEALTH_PARAMS)
            ->getData();

        $searchConnectionResponseTransfer = new SearchConnectionResponseTransfer();
        $searchConnectionResponseTransfer->setIsSuccessfull(true);
        $searchConnectionResponseTransfer->setRawResponse($clusterHealthData);

        return $searchConnectionResponseTransfer;
    }
}
