<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Plugin\ResultFormatter;

use Elastica\ResultSet;
use Spryker\Client\SearchElasticsearch\Plugin\QueryExpander\SpellingSuggestionQueryExpanderPlugin;

/**
 * @method \Spryker\Client\SearchElasticsearch\SearchElasticsearchFactory getFactory()
 */
class SpellingSuggestionResultFormatterPlugin extends AbstractElasticsearchResultFormatterPlugin
{
    /**
     * @var string
     */
    public const NAME = 'spellingSuggestion';

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return string
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * @param \Elastica\ResultSet $searchResult
     * @param array<string, mixed> $requestParameters
     *
     * @return string|null
     */
    protected function formatSearchResult(ResultSet $searchResult, array $requestParameters): ?string
    {
        $suggests = $searchResult->getSuggests();
        $spellingSuggestion = $this->extractSpellingSuggestion($suggests);

        return $spellingSuggestion;
    }

    /**
     * @param array $suggests
     *
     * @return string|null
     */
    protected function extractSpellingSuggestion(array $suggests): ?string
    {
        if (!isset($suggests[SpellingSuggestionQueryExpanderPlugin::SUGGESTION_NAME])) {
            return null;
        }

        $suggest = false;
        $suggestionParts = [];

        foreach ($suggests[SpellingSuggestionQueryExpanderPlugin::SUGGESTION_NAME] as $item) {
            if ($item['options']) {
                $suggest = true;
                $suggestionParts[] = $item['options'][0]['text'];

                continue;
            }

            $suggestionParts[] = $item['text'];
        }

        if (!$suggest) {
            return null;
        }

        return implode(' ', $suggestionParts);
    }
}
