<?php

declare(strict_types=1);

use MyVendor\BeMart\Bootstrap;

$sessionPath = __DIR__ . '/../../var/tmp/http-prod/session';
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

$adminId = $_SERVER['HTTP_X_BEMART_TEST_ADMIN_ID'] ?? null;
if (is_string($adminId) && $adminId !== '') {
    $_SESSION['admin_id'] = $adminId;
}

$customerId = $_SERVER['HTTP_X_BEMART_TEST_CUSTOMER_ID'] ?? null;
if (is_string($customerId) && $customerId !== '') {
    $_SESSION['customer_id'] = $customerId;
}

$csrfToken = $_SERVER['HTTP_X_BEMART_TEST_CSRF_TOKEN'] ?? null;
if (is_string($csrfToken) && $csrfToken !== '') {
    $_SESSION['_csrf_token'] = $csrfToken;
}

require __DIR__ . '/../../vendor/autoload.php';

exit((new Bootstrap())('prod-eccube-sql-hal-app', $GLOBALS, $_SERVER));
