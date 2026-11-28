<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Tests\Hypermedia\FlowCustomerPurchaseTest as Workflow;
use MyVendor\BeMart\Tests\Support\LocalServerHost;

final class FlowCustomerPurchaseTest extends Workflow
{
    protected function newResource(): ResourceInterface
    {
        return new HttpResource(
            LocalServerHost::for(self::class)->host,
            __DIR__ . '/prod-json-index.php',
            __DIR__ . '/log/' . self::FLOW_ID . '.log',
        );
    }
}
