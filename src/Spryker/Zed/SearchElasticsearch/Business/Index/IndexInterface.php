<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SearchElasticsearch\Business\Index;

use Generated\Shared\Transfer\ElasticsearchSearchContextTransfer;
use Generated\Shared\Transfer\SearchContextTransfer;

interface IndexInterface
{
    public function openIndex(SearchContextTransfer $searchContextTransfer): bool;

    public function openIndexes(?string $storeName = null): bool;

    public function closeIndex(SearchContextTransfer $searchContextTransfer): bool;

    public function closeIndexes(?string $storeName = null): bool;

    public function deleteIndex(SearchContextTransfer $searchContextTransfer): bool;

    public function deleteIndexes(?string $storeName = null): bool;

    public function copyIndex(SearchContextTransfer $sourceSearchContextTransfer, SearchContextTransfer $targetSearchContextTransfer): bool;

    public function getDocumentsTotalCount(ElasticsearchSearchContextTransfer $elasticsearchSearchContextTransfer): int;

    public function getIndexMetaData(ElasticsearchSearchContextTransfer $elasticsearchSearchContextTransfer): array;

    /**
     * @param string|null $storeName
     *
     * @return array<string>
     */
    public function getIndexNames(?string $storeName = null): array;
}
