<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Tests\Hypermedia\FlowAdminTemplateLifecycleTest as Workflow;

final class FlowAdminTemplateLifecycleTest extends Workflow
{
    protected function newResource(): ResourceInterface
    {
        return new HttpResource(
            '127.0.0.1:8091',
            __DIR__ . '/prod-json-index.php',
            __DIR__ . '/log/' . self::FLOW_ID . '.log',
        );
    }
}
