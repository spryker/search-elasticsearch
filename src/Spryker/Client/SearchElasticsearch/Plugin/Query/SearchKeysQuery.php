<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Plugin\Query;

use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\MatchAll;
use Elastica\Query\MultiMatch;
use Generated\Shared\Search\PageIndexMap;
use Generated\Shared\Transfer\SearchContextTransfer;
use Spryker\Client\SearchElasticsearch\SearchElasticsearchConfig;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface;
use Spryker\Client\SearchExtension\Dependency\Plugin\SearchContextAwareQueryInterface;

/**
 * @deprecated Use {@link \Spryker\Zed\SearchElasticsearchGui\Communication\Plugin\Query\DocumentListQuery} instead.
 */
class SearchKeysQuery implements QueryInterface, SearchContextAwareQueryInterface
{
    /**
     * @var string
     */
    protected const SOURCE_IDENTIFIER = 'page';

    /**
     * @var string
     */
    protected $searchString;

    /**
     * @var int|null
     */
    protected $limit;

    /**
     * @var int|null
     */
    protected $offset;

    /**
     * @var \Spryker\Client\SearchElasticsearch\SearchElasticsearchConfig
     */
    protected $config;

    /**
     * @var \Generated\Shared\Transfer\SearchContextTransfer
     */
    protected $searchContextTransfer;

    /**
     * @param string $searchString
     * @param \Spryker\Client\SearchElasticsearch\SearchElasticsearchConfig $config
     * @param int|null $limit
     * @param int|null $offset
     */
    public function __construct(string $searchString, SearchElasticsearchConfig $config, ?int $limit = null, ?int $offset = null)
    {
        $this->searchString = $searchString;
        $this->limit = $limit;
        $this->config = $config;
        $this->offset = $offset;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return \Elastica\Query\MatchAll|\Elastica\Query
     */
    public function getSearchQuery()
    {
        $baseQuery = new Query();

        if ($this->searchString) {
            $query = $this->createFullTextSearchQuery($this->searchString);
        } else {
            $query = new MatchAll();
        }

        $baseQuery->setQuery($query);

        $this->setLimit($baseQuery);
        $this->setOffset($baseQuery);

        $baseQuery->setExplain(true);

        return $baseQuery;
    }

    /**
     * {@inheritDoc}
     * - Defines a context for keys search.
     *
     * @api
     *
     * @return \Generated\Shared\Transfer\SearchContextTransfer
     */
    public function getSearchContext(): SearchContextTransfer
    {
        if (!$this->hasSearchContext()) {
            $this->setupDefaultSearchContext();
        }

        return $this->searchContextTransfer;
    }

    /**
     * {@inheritDoc}
     * - Sets a context for keys search.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\SearchContextTransfer $searchContextTransfer
     *
     * @return void
     */
    public function setSearchContext(SearchContextTransfer $searchContextTransfer): void
    {
        $this->searchContextTransfer = $searchContextTransfer;
    }

    /**
     * @param string $searchString
     *
     * @return \Elastica\Query\BoolQuery
     */
    protected function createFullTextSearchQuery(string $searchString): BoolQuery
    {
        $fields = [
            PageIndexMap::FULL_TEXT,
            sprintf('%s^%d', PageIndexMap::FULL_TEXT_BOOSTED, $this->config->getFullTextBoostedBoostingValue()),
        ];

        $multiMatch = (new MultiMatch())
            ->setFields($fields)
            ->setQuery($searchString)
            ->setType(MultiMatch::TYPE_CROSS_FIELDS);

        $boolQuery = (new BoolQuery())
            ->addMust($multiMatch);

        return $boolQuery;
    }

    /**
     * @param \Elastica\Query $baseQuery
     *
     * @return void
     */
    protected function setLimit(Query $baseQuery): void
    {
        if ($this->limit !== null) {
            $baseQuery->setSize($this->limit);
        }
    }

    /**
     * @param \Elastica\Query $baseQuery
     *
     * @return void
     */
    protected function setOffset(Query $baseQuery): void
    {
        if ($this->offset !== null) {
            $baseQuery->setFrom($this->offset);
        }
    }

    /**
     * @return void
     */
    protected function setupDefaultSearchContext(): void
    {
        $searchContextTransfer = new SearchContextTransfer();
        $searchContextTransfer->setSourceIdentifier(static::SOURCE_IDENTIFIER);

        $this->searchContextTransfer = $searchContextTransfer;
    }

    /**
     * @return bool
     */
    protected function hasSearchContext(): bool
    {
        return (bool)$this->searchContextTransfer;
    }
}
