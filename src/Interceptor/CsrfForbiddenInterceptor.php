<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Interceptor;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\Csrf\Attribute\CsrfToken;
use Ray\Csrf\CsrfTokenInterface;
use Ray\Csrf\Http\CsrfTokenField;
use Ray\Csrf\Http\RequestTokenInterface;

/**
 * Verifies CSRF tokens using Ray.Csrf's own field resolution and
 * header/query/post token lookup (Http\RequestTokenInterface), but keeps
 * BeMart's established, non-throwing 403 ResourceObject contract instead of
 * Ray.Csrf's own Interceptor\CsrfTokenInterceptor, for two reasons
 * (see docs/methodology/csrf-protection.md):
 *
 *  - Every mutating Resource test asserts `$ro->code`/`$ro->body['message']`
 *    directly, never `expectException()`, for a CSRF rejection.
 *  - Ray.Csrf's own interceptor always rejects a missing token before ever
 *    consulting CsrfTokenInterface::verify(), which would break
 *    Fake\Service\NullCsrfToken's contract of accepting any request —
 *    including one with no token at all — for tests whose subject isn't
 *    CSRF. Calling verify() unconditionally (missing token becomes '')
 *    keeps that decision where BeMart has always made it: in the bound
 *    CsrfTokenInterface.
 */
final readonly class CsrfForbiddenInterceptor implements MethodInterceptor
{
    public function __construct(
        private CsrfTokenInterface $csrf,
        private RequestTokenInterface $requestToken,
        private CsrfTokenField $defaultField,
    ) {
    }

    /** @param MethodInvocation<object> $invocation */
    #[Override]
    public function invoke(MethodInvocation $invocation): mixed
    {
        $attributes = $invocation->getMethod()->getAttributes(CsrfToken::class);
        $attribute = $attributes[0]->newInstance();
        $field = $attribute->field === null ? $this->defaultField : new CsrfTokenField($attribute->field);

        $submitted = $this->requestToken->submitted($invocation, $field);
        if (! $this->csrf->verify($submitted ?? '')) {
            $resourceObject = $invocation->getThis();
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
