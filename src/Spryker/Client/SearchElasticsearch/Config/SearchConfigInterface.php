<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Config;

interface SearchConfigInterface
{
    public function getFacetConfig(): FacetConfigInterface;

    public function getSortConfig(): SortConfigInterface;

    public function getPaginationConfig(): PaginationConfigInterface;
}
