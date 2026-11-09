<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\SearchElasticsearch\WebProfiler\DataCollector;

use Spryker\Shared\SearchElasticsearch\Dependency\Service\SearchElasticsearchToUtilEncodingServiceInterface;
use Spryker\Shared\SearchElasticsearch\Logger\ElasticsearchLoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Throwable;

class ElasticsearchDataCollector extends DataCollector implements LateDataCollectorInterface
{
    protected const string DATA_COLLECTOR_NAME = 'elasticsearch';

    protected const string KEY_LOGS = 'logs';

    protected const string KEY_DESTINATION = 'destination';

    protected const string KEY_PAYLOAD = 'payload';

    protected const string KEY_RESULT = 'result';

    public function __construct(
        protected ElasticsearchLoggerInterface $elasticsearchLogger,
        protected SearchElasticsearchToUtilEncodingServiceInterface $utilEncodingService,
    ) {
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
    }

    public function lateCollect(): void
    {
        $logs = [];

        foreach ($this->elasticsearchLogger->getLogs() as $log) {
            $logs[] = [
                static::KEY_DESTINATION => $log[static::KEY_DESTINATION] ?? null,
                static::KEY_PAYLOAD => $this->cloneVar($this->decodeLogValue($log[static::KEY_PAYLOAD] ?? null)),
                static::KEY_RESULT => $this->cloneVar($this->decodeLogValue($log[static::KEY_RESULT] ?? null)),
            ];
        }

        $this->data[static::KEY_LOGS] = $logs;
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
     * @return array<int, array<string, mixed>>
     */
    public function getLogs(): array
    {
        return $this->data[static::KEY_LOGS] ?? [];
    }

    protected function decodeLogValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return $this->utilEncodingService->decodeJson($value, true) ?? $value;
    }
}
