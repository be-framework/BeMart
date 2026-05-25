<?php

declare(strict_types=1);

putenv('APP_CONTEXT=html-test-hal-api-app');

$sessionPath = __DIR__ . '/../../var/tmp/html/session';
if (! is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}

session_save_path($sessionPath);

require __DIR__ . '/../../public/index.php';
