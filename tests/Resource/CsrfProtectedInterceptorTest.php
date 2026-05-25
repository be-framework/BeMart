<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use ArrayObject;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Exception\CsrfTokenInvalidException;
use MyVendor\BeMart\Interceptor\CsrfProtectedInterceptor;
use MyVendor\BeMart\Support\Resource\RequestQueryContext;
use PHPUnit\Framework\TestCase;
use Ray\Aop\MethodInvocation;
use Ray\Aop\ReflectionMethod;

final class CsrfProtectedInterceptorTest extends TestCase
{
    public function testValidTokenProceeds(): void
    {
        $context = new RequestQueryContext();
        $context->push(['csrfToken' => FakeCsrfToken::TOKEN]);
        $invocation = new CsrfProtectedMethodInvocation();

        $result = (new CsrfProtectedInterceptor(new FakeCsrfToken(), $context))->invoke($invocation);

        $this->assertSame('proceeded', $result);
        $this->assertTrue($invocation->proceeded);
    }

    public function testInvalidTokenThrowsForbiddenException(): void
    {
        $context = new RequestQueryContext();
        $context->push(['csrfToken' => 'attacker-token']);

        $this->expectException(CsrfTokenInvalidException::class);

        (new CsrfProtectedInterceptor(new FakeCsrfToken(), $context))->invoke(new CsrfProtectedMethodInvocation());
    }

    public function testMissingTokenThrowsForbiddenException(): void
    {
        $context = new RequestQueryContext();
        $context->push([]);

        $this->expectException(CsrfTokenInvalidException::class);

        (new CsrfProtectedInterceptor(new FakeCsrfToken(), $context))->invoke(new CsrfProtectedMethodInvocation());
    }

    public function testCustomBodyFieldIsUsed(): void
    {
        $context = new RequestQueryContext();
        $context->push(['_csrf' => FakeCsrfToken::TOKEN]);
        $invocation = new CsrfProtectedMethodInvocation('onPostWithCustomBodyField');

        $result = (new CsrfProtectedInterceptor(new FakeCsrfToken(), $context))->invoke($invocation);

        $this->assertSame('proceeded', $result);
        $this->assertTrue($invocation->proceeded);
    }
}

final class CsrfProtectedFixture
{
    #[CsrfProtected]
    public function onPost(): void
    {
    }

    #[CsrfProtected(bodyField: '_csrf')]
    public function onPostWithCustomBodyField(): void
    {
    }
}

/** @implements MethodInvocation<object> */
final class CsrfProtectedMethodInvocation implements MethodInvocation
{
    public bool $proceeded = false;

    public function __construct(
        private readonly string $methodName = 'onPost',
    ) {
    }

    public function getMethod(): ReflectionMethod
    {
        return new ReflectionMethod(CsrfProtectedFixture::class, $this->methodName);
    }

    public function getArguments(): ArrayObject
    {
        return new ArrayObject([]);
    }

    public function getNamedArguments(): ArrayObject
    {
        return new ArrayObject([]);
    }

    public function proceed(): mixed
    {
        $this->proceeded = true;

        return 'proceeded';
    }

    public function getThis(): object
    {
        return new CsrfProtectedFixture();
    }
}
