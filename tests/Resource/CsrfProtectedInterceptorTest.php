<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use ArrayObject;
use BEAR\Resource\Code;
use BEAR\Resource\NullUri;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Interceptor\CsrfProtectedInterceptor;
use PHPUnit\Framework\TestCase;
use Ray\Aop\MethodInvocation;
use Ray\Aop\ReflectionMethod;

final class CsrfProtectedInterceptorTest extends TestCase
{
    public function testValidTokenProceeds(): void
    {
        $invocation = new CsrfProtectedMethodInvocation(query: ['csrfToken' => FakeCsrfToken::TOKEN]);

        $result = (new CsrfProtectedInterceptor(new FakeCsrfToken()))->invoke($invocation);

        $this->assertSame('proceeded', $result);
        $this->assertTrue($invocation->proceeded);
    }

    public function testInvalidTokenReturnsForbiddenResourceObject(): void
    {
        $invocation = new CsrfProtectedMethodInvocation(query: ['csrfToken' => 'attacker-token']);

        $result = (new CsrfProtectedInterceptor(new FakeCsrfToken()))->invoke($invocation);

        $this->assertInstanceOf(ResourceObject::class, $result);
        $this->assertSame(Code::FORBIDDEN, $result->code);
        $this->assertSame(['message' => 'Invalid or missing CSRF token.'], $result->body);
        $this->assertFalse($invocation->proceeded);
    }

    public function testMissingTokenReturnsForbiddenResourceObject(): void
    {
        $invocation = new CsrfProtectedMethodInvocation(query: []);

        $result = (new CsrfProtectedInterceptor(new FakeCsrfToken()))->invoke($invocation);

        $this->assertInstanceOf(ResourceObject::class, $result);
        $this->assertSame(Code::FORBIDDEN, $result->code);
        $this->assertSame(['message' => 'Invalid or missing CSRF token.'], $result->body);
        $this->assertFalse($invocation->proceeded);
    }

    public function testCustomBodyFieldIsUsed(): void
    {
        $invocation = new CsrfProtectedMethodInvocation(
            methodName: 'onPostWithCustomBodyField',
            query: ['_csrf' => FakeCsrfToken::TOKEN],
        );

        $result = (new CsrfProtectedInterceptor(new FakeCsrfToken()))->invoke($invocation);

        $this->assertSame('proceeded', $result);
        $this->assertTrue($invocation->proceeded);
    }
}

final class CsrfProtectedFixture extends ResourceObject
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
    private readonly CsrfProtectedFixture $resourceObject;

    public function __construct(
        private readonly string $methodName = 'onPost',
        array $query = [],
    ) {
        $uri = new NullUri();
        $uri->query = $query;
        $this->resourceObject = new CsrfProtectedFixture();
        $this->resourceObject->uri = $uri;
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
        return $this->resourceObject;
    }
}
