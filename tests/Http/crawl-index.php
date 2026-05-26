<?php

declare(strict_types=1);

use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Auth\HtmlSessionAdapter;

// Serve real public assets (CSS/JS/images) before routing pages. Koriym's
// test server invokes this script as a router without necessarily setting
// public/ as the document root, so `return false` is not enough here: the
// browser would receive a 404 for /assets/css/style.css and render pages
// without CSS.
$requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$publicRoot = realpath(__DIR__ . '/../../public');
$publicFile = realpath(__DIR__ . '/../../public' . $requestPath);
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

    if ($requestPath === '/assets/css/customize.css' || $requestPath === '/assets/js/customize.js') {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    header('Content-Type: ' . $contentType);
    header('Content-Length: ' . (string) filesize($publicFile));
    readfile($publicFile);

    return true;
}

require __DIR__ . '/../../vendor/autoload.php';

putenv('APP_CONTEXT=html-test-hal-api-app');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE & ~E_USER_NOTICE);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$sessionPath = __DIR__ . '/../../var/tmp/html/session';
if (! is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}

session_save_path($sessionPath);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'use_strict_mode' => false,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY] = 'ad000000000000000000000000000001';
$_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY] = str_contains($requestUri, '/mypage/history')
    ? 'customer-001'
    : '0123456789abcdef0123456789abcdef';

require __DIR__ . '/../../public/index.php';
