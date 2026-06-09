<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use MyVendor\BeMart\Tests\Hypermedia\WorkflowTest as Workflow;

final class WorkflowTest extends Workflow
{
    protected function setUp(): void
    {
        $this->resource = new HttpResource('127.0.0.1:18080', __DIR__ . '/index.php', __DIR__ . '/log/workflow.log');
    }
}
