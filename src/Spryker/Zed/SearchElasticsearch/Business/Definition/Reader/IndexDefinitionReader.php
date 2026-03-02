<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SearchElasticsearch\Business\Definition\Reader;

use Spryker\Shared\SearchElasticsearch\Dependency\Service\SearchElasticsearchToUtilEncodingServiceInterface;
use Symfony\Component\Finder\SplFileInfo;

class IndexDefinitionReader implements IndexDefinitionReaderInterface
{
    /**
     * @var \Spryker\Shared\SearchElasticsearch\Dependency\Service\SearchElasticsearchToUtilEncodingServiceInterface
     */
    protected $utilEncodingService;

    public function __construct(SearchElasticsearchToUtilEncodingServiceInterface $utilEncodingService)
    {
        $this->utilEncodingService = $utilEncodingService;
    }

    public function read(SplFileInfo $fileInfo): array
    {
        return $this->utilEncodingService->decodeJson($fileInfo->getContents(), true);
    }
}
