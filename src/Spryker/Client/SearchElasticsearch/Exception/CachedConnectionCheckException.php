<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Exception;

use Exception;

/**
 * Replays a connection failure that was observed and cached a moment ago, so that a burst of
 * health check probes does not each re-query a search engine that is already known to be failing.
 * The original exception class is not preserved; its message is.
 */
class CachedConnectionCheckException extends Exception
{
}
