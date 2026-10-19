<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Fake\Csrf;

use BEAR\Csrf\Http\CsrfTokenField;
use BEAR\Csrf\Http\RequestTokenInterface;
use Override;
use Ray\Aop\MethodInvocation;

/** Scripts what the request carried, so a test can drive the token gate without a real submission. */
final readonly class FakeRequestToken implements RequestTokenInterface
{
    public function __construct(private string|null $submitted = FakeCsrfToken::DEFAULT_TOKEN)
    {
    }

    /** @param MethodInvocation<object> $invocation */
    #[Override]
    public function submitted(MethodInvocation $invocation, CsrfTokenField $field): string|null
    {
        return $this->submitted;
    }
}
