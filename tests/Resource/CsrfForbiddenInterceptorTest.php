<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use ArrayObject;
use BEAR\Resource\Code;
use BEAR\Resource\NullUri;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Interceptor\CsrfForbiddenInterceptor;
use PHPUnit\Framework\TestCase;
use Ray\Aop\MethodInvocation;
use Ray\Aop\ReflectionMethod;
use BEAR\Csrf\Attribute\CsrfToken;
use BEAR\Csrf\Http\CompositeRequestToken;
use BEAR\Csrf\Http\CsrfTokenField;
use BEAR\Csrf\Http\HeaderRequestToken;
use BEAR\Csrf\Http\PostRequestToken;
use BEAR\Csrf\Http\ResourceQueryRequestToken;

final class CsrfForbiddenInterceptorTest extends TestCase
{
    public function testValidTokenProceeds(): void
    {
        $invocation = new CsrfForbiddenMethodInvocation(query: ['csrfToken' => FakeCsrfToken::TOKEN]);

        $result = $this->interceptor()->invoke($invocation);

        $this->assertSame('proceeded', $result);
        $this->assertTrue($invocation->proceeded);
    }

    public function testInvalidTokenReturnsForbiddenResourceObject(): void
    {
        $invocation = new CsrfForbiddenMethodInvocation(query: ['csrfToken' => 'attacker-token']);

        $result = $this->interceptor()->invoke($invocation);

        $this->assertInstanceOf(ResourceObject::class, $result);
        $this->assertSame(Code::FORBIDDEN, $result->code);
        $this->assertSame(['message' => 'Invalid or missing CSRF token.'], $result->body);
        $this->assertFalse($invocation->proceeded);
    }

    public function testMissingTokenReturnsForbiddenResourceObject(): void
    {
        $invocation = new CsrfForbiddenMethodInvocation(query: []);

        $result = $this->interceptor()->invoke($invocation);

        $this->assertInstanceOf(ResourceObject::class, $result);
        $this->assertSame(Code::FORBIDDEN, $result->code);
        $this->assertSame(['message' => 'Invalid or missing CSRF token.'], $result->body);
        $this->assertFalse($invocation->proceeded);
    }

    public function testCustomFieldNameIsUsed(): void
    {
        $invocation = new CsrfForbiddenMethodInvocation(
            methodName: 'onPostWithCustomField',
            query: ['_csrf' => FakeCsrfToken::TOKEN],
        );

        $result = $this->interceptor()->invoke($invocation);

        $this->assertSame('proceeded', $result);
        $this->assertTrue($invocation->proceeded);
    }

    /**
     * A missing token must still be forbidden when the bound CsrfTokenInterface
     * is strict (production), even though Fake\Service\NullCsrfToken (see
     * FakeModule) is intentionally lenient about it for non-CSRF-focused tests.
     */
    public function testMissingTokenIsForbiddenEvenWithoutAnAttackerSuppliedValue(): void
    {
        $invocation = new CsrfForbiddenMethodInvocation(query: ['unrelatedField' => 'x']);

        $result = $this->interceptor()->invoke($invocation);

        $this->assertInstanceOf(ResourceObject::class, $result);
        $this->assertSame(Code::FORBIDDEN, $result->code);
    }

    /** Builds the interceptor exactly as AppModule wires it, minus DI. */
    private function interceptor(): CsrfForbiddenInterceptor
    {
        $requestToken = new CompositeRequestToken(
            new HeaderRequestToken(),
            new ResourceQueryRequestToken(),
            new PostRequestToken(),
        );

        return new CsrfForbiddenInterceptor(new FakeCsrfToken(), $requestToken, new CsrfTokenField('csrfToken'));
    }
}

final class CsrfForbiddenFixture extends ResourceObject
{
    #[CsrfToken]
    public function onPost(): void
    {
    }

    #[CsrfToken(field: '_csrf')]
    public function onPostWithCustomField(): void
    {
    }
}

/** @implements MethodInvocation<object> */
final class CsrfForbiddenMethodInvocation implements MethodInvocation
{
    public bool $proceeded = false;
    private readonly CsrfForbiddenFixture $resourceObject;

    /** @param array<string, string> $query */
    public function __construct(
        private readonly string $methodName = 'onPost',
        array $query = [],
    ) {
        $uri = new NullUri();
        $uri->query = $query;
        $this->resourceObject = new CsrfForbiddenFixture();
        $this->resourceObject->uri = $uri;
    }

    public function getMethod(): ReflectionMethod
    {
        return new ReflectionMethod(CsrfForbiddenFixture::class, $this->methodName);
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
