<?php

declare(strict_types=1);

use MyVendor\BeMart\Bootstrap;

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

// HTTP tests run the HTML adapter but still use the deterministic FakeQuery
// corpus. Pin the cart session to the fake fixture prefix; real HTML entry
// points keep using the session-derived prefix.
$_SESSION['cart_session_prefix'] ??= 'session-prefix-1';

require __DIR__ . '/../../vendor/autoload.php';

exit((new Bootstrap())('html-test-hal-app', $GLOBALS, $_SERVER));
