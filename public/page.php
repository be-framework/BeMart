<?php

declare(strict_types=1);

use MyVendor\BeMart\Bootstrap;

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_string($path) && $path !== '/' && is_file(__DIR__ . $path)) {
        return false;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';

exit((new Bootstrap())('html-eccube-sql-hal-app', $GLOBALS, $_SERVER));
