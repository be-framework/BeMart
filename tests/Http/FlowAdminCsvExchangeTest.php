<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Tests\Hypermedia\FlowAdminCsvExchangeTest as Workflow;

final class FlowAdminCsvExchangeTest extends Workflow
{
    protected function newResource(): ResourceInterface
    {
        return new HttpResource(
            '127.0.0.1:8081',
            __DIR__ . '/json-index.php',
            __DIR__ . '/log/' . self::FLOW_ID . '.log',
        );
    }
}
