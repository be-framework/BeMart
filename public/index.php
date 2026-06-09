<?php

declare(strict_types=1);

use MyVendor\BeMart\Bootstrap;

require dirname(__DIR__) . '/vendor/autoload.php';

exit((new Bootstrap())(PHP_SAPI === 'cli-server' ? 'sql-hal-app' : 'prod-eccube-sql-hal-app', $GLOBALS, $_SERVER));
