<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SearchElasticsearch\Business\Definition\Merger;

class IndexDefinitionMerger implements IndexDefinitionMergerInterface
{
    public function merge(array $definition1, array $definition2): array
    {
        return array_replace_recursive($definition1, $definition2);
    }
}
