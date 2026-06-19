<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Fake\Http;

use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Override;
use Ray\Aop\MethodInvocation;
use Ray\Csrf\Http\CsrfTokenField;
use Ray\Csrf\Http\RequestTokenInterface;

/**
 * Test-default request-token reader.
 *
 * Always reports a submitted token ({@see FakeCsrfToken::TOKEN}) so the
 * {@see \Ray\Csrf\Interceptor\CsrfTokenInterceptor} never throws
 * MissingCsrfTokenException in fake/smoke contexts — paired with
 * {@see \MyVendor\BeMart\Be\Reason\Fake\Service\NullCsrfToken} /
 * {@see FakeCsrfToken}, whose verify() accepts it. Bypasses the real
 * CompositeRequestToken so tests that omit a CSRF field still exercise the
 * protected method.
 */
final class NullRequestToken implements RequestTokenInterface
{
    /** @param MethodInvocation<object> $invocation */
    #[Override]
    public function submitted(MethodInvocation $invocation, CsrfTokenField $field): string|null
    {
        return FakeCsrfToken::TOKEN;
    }
}
