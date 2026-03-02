<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Suggest;

use Elastica\Suggest\Completion;
use Elastica\Suggest\Phrase;
use Elastica\Suggest\Term;

interface SuggestBuilderInterface
{
    public function createTerm(string $name, string $field): Term;

    public function createComplete(string $name, string $field): Completion;

    public function createPhrase(string $name, string $field): Phrase;
}
