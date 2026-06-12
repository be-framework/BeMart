<?php

declare(strict_types=1);

use MyVendor\BeMart\Bootstrap;

require dirname(__DIR__) . '/vendor/autoload.php';

$context = getenv('APP_CONTEXT') ?: 'cli-html-eccube-sql-hal-app';

exit((new Bootstrap())($context, $GLOBALS, $_SERVER));
