<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\AggregationExtractor;

use Generated\Shared\Transfer\FacetConfigTransfer;

interface AggregationExtractorFactoryInterface
{
    public function create(FacetConfigTransfer $facetConfigTransfer): AggregationExtractorInterface;
}
