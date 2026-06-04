<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Tests\Hypermedia\FlowAdminMasterDataUpdateTest as Workflow;

final class FlowAdminMasterDataUpdateTest extends Workflow
{
    protected function newResource(): ResourceInterface
    {
        return new HttpResource(
            '127.0.0.1:8082',
            __DIR__ . '/admin-json-index.php',
            __DIR__ . '/log',
        );
    }
}
