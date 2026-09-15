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
    public const string FULL_TEXT_BOOSTED_BOOSTING_VALUE = 'SEARCH_ELASTICSEARCH:FULL_TEXT_BOOSTED_BOOSTING_VALUE';

    /**
     * Specification:
     * - Elasticsearch connection host name. (Required).
     *
     * @api
     *
     * @var string
     */
    public const string HOST = 'SEARCH_ELASTICSEARCH:HOST';

    /**
     * Specification:
     * - Elasticsearch connection port number. (Required).
     *
     * @api
     *
     * @var string
     */
    public const string PORT = 'SEARCH_ELASTICSEARCH:PORT';

    /**
     * Specification:
     * - Elasticsearch connection transport name (i.e. "http"). (Required).
     *
     * @api
     *
     * @var string
     */
    public const string TRANSPORT = 'SEARCH_ELASTICSEARCH:TRANSPORT';

    /**
     * Specification:
     * - Elasticsearch connection authentication header parameters. (Optional).
     *
     * @api
     *
     * @var string
     */
    public const string AUTH_HEADER = 'SEARCH_ELASTICSEARCH:AUTH_HEADER';

    /**
     * Specification:
     * - Defines an array of extra Elasticsearch connection parameters (i.e. ['foo' => 'bar', ...]). (Optional)
     *
     * @api
     *
     * @var string
     */
    public const string EXTRA = 'SEARCH_ELASTICSEARCH:EXTRA';

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
    public const string CLIENT_CONFIGURATION = 'SEARCH_ELASTICSEARCH:CLIENT_CONFIGURATION';

    /**
     * Specification:
     * - Sets the permission mode for generated directories.
     *
     * @api
     *
     * @var string
     */
    public const string DIRECTORY_PERMISSION = 'SEARCH_ELASTICSEARCH:DIRECTORY_PERMISSION';

    /**
     * Specification:
     * - Defines prefix for ElasticSearch indexes.
     *
     * @api
     *
     * @var string
     */
    public const string INDEX_PREFIX = 'SEARCH_ELASTICSEARCH:INDEX_PREFIX';

    /**
     * Specification:
     * - Defines for how many seconds a SUCCESSFUL connection check result is reused.
     * - Health check probes hit this check on every request; caching collapses a burst of
     *   simultaneous probes into a single call to the search engine.
     * - Set to 0 to disable caching of successful results.
     *
     * @api
     *
     * @var string
     */
    public const string CONNECTION_CHECK_CACHE_TTL_SECONDS = 'SEARCH_ELASTICSEARCH:CONNECTION_CHECK_CACHE_TTL_SECONDS';

    /**
     * Specification:
     * - Defines for how many seconds a FAILED connection check result is reused.
     * - Kept shorter than the successful TTL so that a recovered search engine is detected quickly;
     *   protection against re-querying an unhealthy engine comes from the single-flight lock, not
     *   from a long negative TTL.
     * - Set to 0 to disable caching of failed results.
     *
     * @api
     *
     * @var string
     */
    public const string CONNECTION_CHECK_CACHE_FAILURE_TTL_SECONDS = 'SEARCH_ELASTICSEARCH:CONNECTION_CHECK_CACHE_FAILURE_TTL_SECONDS';

    /**
     * Specification:
     * - Defines the read timeout, in seconds, for the connection check only.
     * - The shared search client keeps its own timeout; this one applies solely to the health
     *   check, so a slow search engine cannot hold a PHP worker for the Elastica default of 300
     *   seconds while leaving real search traffic untouched.
     * - Set to 0 to inherit the timeout from the regular client configuration.
     *
     * @api
     *
     * @var string
     */
    public const string CONNECTION_CHECK_TIMEOUT_SECONDS = 'SEARCH_ELASTICSEARCH:CONNECTION_CHECK_TIMEOUT_SECONDS';

    /**
     * Specification:
     * - Defines the connection-phase timeout, in seconds, for the connection check only.
     * - Set to 0 to inherit the connect timeout from the regular client configuration.
     *
     * @api
     *
     * @var string
     */
    public const string CONNECTION_CHECK_CONNECT_TIMEOUT_SECONDS = 'SEARCH_ELASTICSEARCH:CONNECTION_CHECK_CONNECT_TIMEOUT_SECONDS';

    /**
     * Specification:
     * - Defines the directory holding the connection check cache file, used where APCu is not
     *   available.
     * - MUST be container-local. A path on shared storage would let one task's result answer for
     *   another task, which defeats the purpose of a per-task liveness probe.
     * - Defaults to the system temporary directory.
     *
     * @api
     *
     * @var string
     */
    public const string CONNECTION_CHECK_CACHE_DIRECTORY = 'SEARCH_ELASTICSEARCH:CONNECTION_CHECK_CACHE_DIRECTORY';
}
