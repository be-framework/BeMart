<?php

declare(strict_types=1);

use MyVendor\BeMart\Bootstrap;
use MyVendor\BeMart\Tests\Support\HttpStoryModule;

require __DIR__ . '/../../vendor/autoload.php';

// The story client names the customer it signs in as; without the header it is a guest
$customerId = $_SERVER['HTTP_X_BEMART_TEST_CUSTOMER_ID'] ?? null;
$customerId = is_string($customerId) && $customerId !== '' ? $customerId : null;

exit((new Bootstrap())('admin-test-hal-app', $GLOBALS, $_SERVER, new HttpStoryModule($customerId)));
