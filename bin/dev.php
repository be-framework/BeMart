<?php

declare(strict_types=1);

use MyVendor\BeMart\Bootstrap;

require dirname(__DIR__) . '/vendor/autoload.php';

exit((new Bootstrap())('cli-dev-fake-hal-app', $GLOBALS, $_SERVER));
