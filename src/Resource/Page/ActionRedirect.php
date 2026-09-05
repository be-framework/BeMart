<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\JsonSchema;

use function preg_match;

/**
 * Safe HTML endpoint for legacy storefront links whose state transition is
 * performed by JavaScript or by a POST-only route in EC-CUBE.
 *
 * It never renders a placeholder page. The browser is redirected to a stable
 * page so link crawls do not surface "not implemented" copy while templates
 * are migrated to explicit POST forms.
 */
class ActionRedirect extends ResourceObject
{
    /** ALPS `goActionRedirect` に対応する GET 操作。 */
    #[Alps('goActionRedirect')]
    #[JsonSchema(schema: 'get-action-redirect.json', params: 'get-action-redirect.param.json')]
    public function onGet(string|null $returnTo = null): static
    {
        $this->redirect($returnTo);

        return $this;
    }

    /** ALPS `doActionRedirect` に対応する POST 操作。 */
    #[Alps('doActionRedirect')]
    #[JsonSchema(schema: 'post-action-redirect.json', params: 'post-action-redirect.param.json')]
    #[CsrfProtected]
    public function onPost(string|null $returnTo = null): static
    {
        $this->redirect($returnTo);

        return $this;
    }

    private function redirect(string|null $returnTo): void
    {
        $this->code = Code::OK;
        $this->headers['Location'] = $this->safeReturnTo($returnTo);
        $this->body = ['message' => '操作を受け付けました。'];
    }

    /**
     * A `returnTo` is honoured only when it is an absolute path on this
     * origin. The second character must not be a separator: `//host` and
     * `/\host` are both protocol-relative references, because browsers
     * normalise `\` to `/` in an http(s) authority. A backslash,
     * whitespace or control character anywhere else is refused too — it
     * would either re-open the authority question or be smuggled into
     * the `Location` header.
     */
    private function safeReturnTo(string|null $returnTo): string
    {
        if ($returnTo !== null && preg_match('#\A/(?![/\\\\])[^\\\\\s]*\z#', $returnTo) === 1) {
            return $returnTo;
        }

        return '/';
    }
}
