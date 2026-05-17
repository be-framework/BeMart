<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Final\CartItemAdded;
use MyVendor\BeMart\Be\Input\AddCartItemInput;
use MyVendor\BeMart\Module\AppModule;
use Ray\Di\Injector;

$injector = new Injector(
    new AppModule(new Meta('MyVendor\\BeMart', 'test')),
    __DIR__ . '/../var/tmp/test',
);

$becoming = $injector->getInstance(BecomingInterface::class);
echo 'BecomingInterface resolved: ' . $becoming::class . "\n";

$final = $becoming(new AddCartItemInput('sample-001', 2));
assert($final instanceof CartItemAdded);

printf(
    "cartKey=%s adjusted=%d unit=%d total=%d deliveryFeeTotal=%d saleType=%s\n",
    $final->cartKey,
    $final->adjustedQuantity,
    $final->unitPrice,
    $final->totalPrice,
    $final->deliveryFeeTotal,
    $final->saleTypeName,
);
