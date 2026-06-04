<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Tests\Hypermedia\CustomerInquiryWorkflowTest as Workflow;

final class CustomerInquiryWorkflowTest extends Workflow
{
    protected function newResource(): ResourceInterface
    {
        return new HttpResource(
            '127.0.0.1:8081',
            __DIR__ . '/json-index.php',
            __DIR__ . '/log/customer-inquiry-workflow.log',
        );
    }
}
