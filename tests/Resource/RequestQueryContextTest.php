<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use MyVendor\BeMart\Support\Resource\RequestQueryContext;
use PHPUnit\Framework\TestCase;

final class RequestQueryContextTest extends TestCase
{
    public function testReturnsEmptyArrayWhenNoRequestIsActive(): void
    {
        $context = new RequestQueryContext();

        $this->assertSame([], $context->current());
        $this->assertNull($context->get('csrfToken'));
    }

    public function testPushPopAndNestedRequests(): void
    {
        $context = new RequestQueryContext();

        $context->push(['csrfToken' => 'outer', 'id' => 1]);
        $this->assertSame('outer', $context->get('csrfToken'));

        $context->push(['csrfToken' => 'inner']);
        $this->assertSame(['csrfToken' => 'inner'], $context->current());
        $this->assertSame('inner', $context->get('csrfToken'));

        $context->pop();
        $this->assertSame('outer', $context->get('csrfToken'));

        $context->pop();
        $this->assertSame([], $context->current());
    }
}
