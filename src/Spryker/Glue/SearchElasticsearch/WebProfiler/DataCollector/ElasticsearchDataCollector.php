<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\SearchElasticsearch\WebProfiler\DataCollector;

use Spryker\Shared\SearchElasticsearch\Logger\ElasticsearchLoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Throwable;

class ElasticsearchDataCollector extends DataCollector
{
    /**
     * @var string
     */
    protected const DATA_COLLECTOR_NAME = 'elasticsearch';

    /**
     * @var \Spryker\Shared\SearchElasticsearch\Logger\ElasticsearchLoggerInterface
     */
    protected $elasticsearchLogger;

    public function __construct(ElasticsearchLoggerInterface $elasticsearchLogger)
    {
        $this->elasticsearchLogger = $elasticsearchLogger;
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $this->data['logs'] = $this->elasticsearchLogger->getLogs();
    }

    public function getName(): string
    {
        return static::DATA_COLLECTOR_NAME;
    }

    public function reset(): void
    {
        $this->data = [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getLogs(): array
    {
        return $this->data['logs'];
    }
}
