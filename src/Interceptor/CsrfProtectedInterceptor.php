<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Interceptor;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

use function is_string;

final readonly class CsrfProtectedInterceptor implements MethodInterceptor
{
    public function __construct(
        private CsrfToken $csrf,
    ) {
    }

    #[Override]
    public function invoke(MethodInvocation $invocation): mixed
    {
        $attributes = $invocation->getMethod()->getAttributes(CsrfProtected::class);
        $attribute = $attributes[0]->newInstance();
        $resourceObject = $invocation->getThis();
        $token = $resourceObject instanceof ResourceObject
            ? ($resourceObject->uri->query[$attribute->bodyField] ?? null)
            : null;

        if (! $this->csrf->isValid(is_string($token) ? $token : null)) {
            if ($resourceObject instanceof ResourceObject) {
                $resourceObject->code = Code::FORBIDDEN;
                $resourceObject->body = ['message' => 'Invalid or missing CSRF token.'];

                return $resourceObject;
            }

            return null;
        }

        return $invocation->proceed();
    }
}
