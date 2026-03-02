<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SearchElasticsearch\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\StoreAwareConsole;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractIndexNameAwareSearchIndexConsole extends StoreAwareConsole
{
    /**
     * @var string
     */
    protected const ARGUMENT_INDEX_NAME = 'index-name';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string|null $indexName */
        $indexName = $input->getArgument(static::ARGUMENT_INDEX_NAME);

        if ($indexName) {
            return $this->executeForSingleIndex($indexName);
        }

        return $this->executeForAllIndexes($input);
    }

    abstract protected function executeForSingleIndex(string $indexName): int;

    abstract protected function executeForAllIndexes(InputInterface $input): int;
}
