<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\Exception\BadRequestException;
use BEAR\Resource\Method;
use BEAR\Resource\Request;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Exception\CsrfTokenInvalidException;
use MyVendor\BeMart\Module\TestModule;
use MyVendor\BeMart\Support\Resource\RequestQueryCapturingInvoker;
use MyVendor\BeMart\Support\Resource\RequestQueryContext;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class RequestQueryCapturingInvokerTest extends TestCase
{
    private RequestQueryCapturingInvoker $invoker;
    private RequestQueryContext $context;

    protected function setUp(): void
    {
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );

        $this->invoker = $injector->getInstance(RequestQueryCapturingInvoker::class);
        $this->context = $injector->getInstance(RequestQueryContext::class);
    }

    public function testConvertsCsrfTokenInvalidExceptionToResourceObject(): void
    {
        $resourceObject = new CsrfTokenInvalidResourceObject();
        $request = new Request($this->invoker, $resourceObject, Method::POST, ['csrfToken' => 'attacker-token']);

        $ro = $this->invoker->invoke($request);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertSame(['message' => 'Invalid or missing CSRF token.'], $ro->body);
        $this->assertSame([], $this->context->current());
    }

    public function testRethrowsOtherBadRequestExceptionsAndPopsContext(): void
    {
        $resourceObject = new GenericBadRequestResourceObject();
        $request = new Request($this->invoker, $resourceObject, Method::POST, ['csrfToken' => 'attacker-token']);

        try {
            $this->invoker->invoke($request);
            $this->fail('Expected BadRequestException was not thrown.');
        } catch (BadRequestException $e) {
            $this->assertSame('Generic bad request.', $e->getMessage());
            $this->assertSame([], $this->context->current());
        }
    }
}

final class CsrfTokenInvalidResourceObject extends ResourceObject
{
    public function onPost(): static
    {
        throw new CsrfTokenInvalidException();
    }
}

final class GenericBadRequestResourceObject extends ResourceObject
{
    public function onPost(): static
    {
        throw new BadRequestException('Generic bad request.', Code::BAD_REQUEST);
    }
}
