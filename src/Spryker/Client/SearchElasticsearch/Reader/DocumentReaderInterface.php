<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Reader;

use Generated\Shared\Transfer\SearchDocumentTransfer;

interface DocumentReaderInterface
{
    public function readDocument(SearchDocumentTransfer $searchDocumentTransfer): SearchDocumentTransfer;
}
