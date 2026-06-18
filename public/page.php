<?php

declare(strict_types=1);

use MyVendor\BeMart\Bootstrap;
use MyVendor\BeMart\Dev\DevLogin;
use MyVendor\BeMart\Module\DevloginModule;

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_string($path) && $path !== '/' && is_file(__DIR__ . $path)) {
        return false;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE && ! headers_sent()) {
    session_start([
        'use_strict_mode' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

$context = getenv('APP_CONTEXT') ?: 'html-eccube-sql-hal-app';

// Dev-only 2FA bypass (see MyVendor\BeMart\Dev\DevLogin): active only with
// BEMART_DEV_LOGIN=1 under `php -S` (cli-server) and a non-prod context.
$override = DevLogin::active(getenv(DevLogin::ENV), PHP_SAPI, $context)
    ? new DevloginModule()
    : null;

exit((new Bootstrap())($context, $GLOBALS, $_SERVER, $override));
