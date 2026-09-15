<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Connection;

use Generated\Shared\Transfer\SearchConnectionResponseTransfer;

interface ConnectionInterface
{
    /**
     * Specification:
     * - Verifies that the search engine is reachable.
     * - Returns a response transfer whose raw response holds the cluster health document.
     * - Throws an exception when the search engine cannot be reached.
     *
     * @throws \Exception
     */
    public function checkConnection(): SearchConnectionResponseTransfer;
}
