<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Query;

use Generated\Shared\Transfer\FacetConfigTransfer;
use Spryker\Shared\Money\Dependency\Plugin\MoneyPluginInterface;

class NestedPriceRangeQuery extends NestedRangeQuery
{
    /**
     * @var \Spryker\Shared\Money\Dependency\Plugin\MoneyPluginInterface
     */
    protected $moneyPlugin;

    /**
     * @param \Generated\Shared\Transfer\FacetConfigTransfer $facetConfigTransfer
     * @param array|string $rangeValues
     * @param \Spryker\Client\SearchElasticsearch\Query\QueryBuilderInterface $queryBuilder
     * @param \Spryker\Shared\Money\Dependency\Plugin\MoneyPluginInterface $moneyPlugin
     */
    public function __construct(FacetConfigTransfer $facetConfigTransfer, $rangeValues, QueryBuilderInterface $queryBuilder, MoneyPluginInterface $moneyPlugin)
    {
        $this->moneyPlugin = $moneyPlugin;

        parent::__construct($facetConfigTransfer, $rangeValues, $queryBuilder);
    }

    /**
     * @param array|string $rangeValues
     *
     * @return void
     */
    protected function setMinMaxValues($rangeValues): void
    {
        parent::setMinMaxValues($rangeValues);

        if ($this->minValue !== null) {
            $this->minValue = (string)$this->moneyPlugin->convertDecimalToInteger((float)$this->minValue);
        }

        if ($this->maxValue !== null) {
            $this->maxValue = (string)$this->moneyPlugin->convertDecimalToInteger((float)$this->maxValue);
        }
    }
}
