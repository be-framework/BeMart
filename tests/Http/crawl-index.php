<?php

declare(strict_types=1);

use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Auth\HtmlSessionAdapter;

// Let PHP's built-in server serve real public assets (CSS/JS/images)
// instead of routing them through Bootstrap as page URLs.
$requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$publicFile = __DIR__ . '/../../public' . $requestPath;
if (PHP_SAPI === 'cli-server' && $requestPath !== '/' && is_file($publicFile)) {
    return false;
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
