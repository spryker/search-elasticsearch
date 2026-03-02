<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Writer;

use Elastica\Client;
use Spryker\Shared\SearchElasticsearch\MappingType\MappingTypeSupportDetectorInterface;

/**
 * @deprecated Will be removed once the support of Elasticsearch 6 and lower is dropped.
 */
class DocumentWriterFactory implements DocumentWriterFactoryInterface
{
    /**
     * @var \Spryker\Shared\SearchElasticsearch\MappingType\MappingTypeSupportDetectorInterface
     */
    protected $mappingTypeSupportDetector;

    public function __construct(MappingTypeSupportDetectorInterface $mappingTypeSupportDetector)
    {
        $this->mappingTypeSupportDetector = $mappingTypeSupportDetector;
    }

    public function createDocumentWriter(Client $client): DocumentWriterInterface
    {
        if ($this->mappingTypeSupportDetector->isMappingTypeSupported()) {
            return new MappingTypeAwareDocumentWriter($client);
        }

        return new DocumentWriter($client);
    }
}
