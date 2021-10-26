<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\SearchElasticsearch;

/**
 * Declares global environment configuration keys. Do not use it for other class constants.
 */
interface SearchElasticsearchConstants
{
    /**
     * Specification:
     * - When executing boosted full text search queries the value of this config setting will be used as the boost factor.
     * - I.e. to set the boost factor to 3 add this to your config: `$config[SearchElasticsearchConstants::FULL_TEXT_BOOSTED_BOOSTING_VALUE] = 3;`.
     *
     * @api
     *
     * @var string
     */
    public const FULL_TEXT_BOOSTED_BOOSTING_VALUE = 'SEARCH_ELASTICSEARCH:FULL_TEXT_BOOSTED_BOOSTING_VALUE';

    /**
     * Specification:
     * - Elasticsearch connection host name. (Required).
     *
     * @api
     *
     * @var string
     */
    public const HOST = 'SEARCH_ELASTICSEARCH:HOST';

    /**
     * Specification:
     * - Elasticsearch connection port number. (Required).
     *
     * @api
     *
     * @var string
     */
    public const PORT = 'SEARCH_ELASTICSEARCH:PORT';

    /**
     * Specification:
     * - Elasticsearch connection transport name (i.e. "http"). (Required).
     *
     * @api
     *
     * @var string
     */
    public const TRANSPORT = 'SEARCH_ELASTICSEARCH:TRANSPORT';

    /**
     * Specification:
     * - Elasticsearch connection authentication header parameters. (Optional).
     *
     * @api
     *
     * @var string
     */
    public const AUTH_HEADER = 'SEARCH_ELASTICSEARCH:AUTH_HEADER';

    /**
     * Specification:
     * - Defines an array of extra Elasticsearch connection parameters (i.e. ['foo' => 'bar', ...]). (Optional)
     *
     * @api
     *
     * @var string
     */
    public const EXTRA = 'SEARCH_ELASTICSEARCH:EXTRA';

    /**
     * Specification:
     * - Defines a custom configuration for \Elastica\Client.
     * - This configuration is used exclusively when set, e.g. no other Elastica configuration will be used for the client.
     * - @see http://elastica.io/ for details.
     *
     * @api
     *
     * @var string
     */
    public const CLIENT_CONFIGURATION = 'SEARCH_ELASTICSEARCH:CLIENT_CONFIGURATION';

    /**
     * Specification:
     * - Sets the permission mode for generated directories.
     *
     * @api
     *
     * @var string
     */
    public const DIRECTORY_PERMISSION = 'SEARCH_ELASTICSEARCH:DIRECTORY_PERMISSION';

    /**
     * Specification:
     * - Defines prefix for ElasticSearch indexes.
     *
     * @api
     *
     * @var string
     */
    public const INDEX_PREFIX = 'SEARCH_ELASTICSEARCH:INDEX_PREFIX';
}
