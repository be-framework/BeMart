<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Interceptor;

use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Exception\CsrfTokenInvalidException;
use MyVendor\BeMart\Support\Resource\RequestQueryContext;
use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

use function is_string;

final readonly class CsrfProtectedInterceptor implements MethodInterceptor
{
    public function __construct(
        private CsrfToken $csrf,
        private RequestQueryContext $queryContext,
    ) {
    }

    #[Override]
    public function invoke(MethodInvocation $invocation): mixed
    {
        $attributes = $invocation->getMethod()->getAttributes(CsrfProtected::class);
        $attribute = $attributes[0]->newInstance();
        $token = $this->queryContext->get($attribute->bodyField);

        if (! $this->csrf->isValid(is_string($token) ? $token : null)) {
            throw new CsrfTokenInvalidException();
        }

        return $invocation->proceed();
    }
}
