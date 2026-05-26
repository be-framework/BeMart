<?php

declare(strict_types=1);

use MyVendor\BeMart\Bootstrap;

// When public/index.php is used as PHP's built-in-server router script,
// real static files must be served before Bootstrap. Some invocations use
// `-t public`, others pass this file as a router from the project root; in
// the latter shape `return false` would still 404 because the server's
// document root is not public/. Serve the file here for both shapes.
$requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$publicRoot = realpath(__DIR__);
$publicFile = realpath(__DIR__ . $requestPath);
if (
    PHP_SAPI === 'cli-server'
    && $requestPath !== '/'
    && is_string($publicRoot)
    && is_string($publicFile)
    && str_starts_with($publicFile, $publicRoot . DIRECTORY_SEPARATOR)
    && is_file($publicFile)
) {
    $extension = strtolower((string) pathinfo($publicFile, PATHINFO_EXTENSION));
    $contentType = match ($extension) {
        'css' => 'text/css; charset=UTF-8',
        'js', 'mjs' => 'application/javascript',
        'json', 'map' => 'application/json',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        default => 'application/octet-stream',
    };

    header('Content-Type: ' . $contentType);
    header('Content-Length: ' . (string) filesize($publicFile));
    readfile($publicFile);

    return true;
}

require dirname(__DIR__) . '/vendor/autoload.php';

exit((new Bootstrap())(PHP_SAPI === 'cli-server' ? 'hal-api-app' : 'prod-hal-api-app', $GLOBALS, $_SERVER));
