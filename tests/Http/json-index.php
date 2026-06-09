<?php

declare(strict_types=1);

use MyVendor\BeMart\Bootstrap;

require __DIR__ . '/../../vendor/autoload.php';

exit((new Bootstrap())('http-test-hal-api-app', $GLOBALS, $_SERVER));
