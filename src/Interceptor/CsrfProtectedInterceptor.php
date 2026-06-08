<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Interceptor;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Exception\CsrfTokenInvalidException;
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
        $arguments = $invocation->getNamedArguments();
        $field = $attribute->bodyField;
        $token = $field !== '' ? ($arguments[$field] ?? null) : null;

        if (! $this->csrf->isValid(is_string($token) ? $token : null)) {
            $resource = $invocation->getThis();
            if ($resource instanceof ResourceObject) {
                $resource->code = Code::FORBIDDEN;
                $resource->body = [
                    'message' => 'Invalid or missing CSRF token.',
                    'error' => 'csrf_token_invalid',
                ];

                return $resource;
            }

            throw new CsrfTokenInvalidException();
        }

        return $invocation->proceed();
    }
}
