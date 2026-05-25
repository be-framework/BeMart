<?php

declare(strict_types=1);

use MyVendor\BeMart\Bootstrap;

require dirname(__DIR__) . '/vendor/autoload.php';

exit((new Bootstrap())('cli-html-hal-app', $GLOBALS, $_SERVER));
