<?php

declare(strict_types=1);

use MyVendor\BeMart\Bootstrap;

// When public/index.php is used as PHP's built-in-server router script,
// real static files must be handed back to the server. Otherwise requests
// such as /assets/css/style.css are routed through Bootstrap and become
// JSON 404 responses, so the HTML page appears unstyled.
$requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$publicFile = __DIR__ . $requestPath;
if (PHP_SAPI === 'cli-server' && $requestPath !== '/' && is_file($publicFile)) {
    return false;
}

require dirname(__DIR__) . '/vendor/autoload.php';

exit((new Bootstrap())(PHP_SAPI === 'cli-server' ? 'hal-api-app' : 'prod-hal-api-app', $GLOBALS, $_SERVER));
