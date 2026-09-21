<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Interceptor;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use BEAR\Csrf\Attribute\CsrfToken;
use BEAR\Csrf\CsrfTokenInterface;
use BEAR\Csrf\Http\CsrfTokenField;
use BEAR\Csrf\Http\RequestTokenInterface;

/**
 * Verifies CSRF tokens using BEAR.Csrf's own field resolution and
 * header/query/post token lookup (Http\RequestTokenInterface), but keeps
 * BeMart's established, non-throwing 403 ResourceObject contract instead of
 * BEAR.Csrf's own Interceptor\CsrfTokenInterceptor
 * (see docs/methodology/csrf-protection.md).
 *
 * Every mutating Resource test asserts `$ro->code` / `$ro->body['message']`
 * directly, never `expectException()`, for a CSRF rejection. That is the
 * reason this class exists.
 *
 * It originally had a second reason: BEAR.Csrf's interceptor rejected a
 * missing token before ever consulting CsrfTokenInterface::verify(), which
 * broke Fake\Service\NullCsrfToken's contract of accepting any request.
 * That was reported as BEAR.Csrf#4 and fixed — upstream now submits a missing
 * token as '' — so only the response-shape reason remains.
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
