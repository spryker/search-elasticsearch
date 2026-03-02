<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Dependency\Client;

use Generated\Shared\Transfer\MoneyTransfer;

interface SearchElasticsearchToMoneyClientInterface
{
    public function fromFloat(float $amount, ?string $isoCode = null): MoneyTransfer;
}
