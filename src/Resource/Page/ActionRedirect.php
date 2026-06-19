<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Ray\Csrf\Attribute\CsrfToken;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use BEAR\Resource\Annotation\JsonSchema;

use function str_starts_with;

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
    public function __construct(
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

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
    #[CsrfToken]
    public function onPost(string|null $returnTo = null): static
    {
        $this->redirect($returnTo);

        return $this;
    }

    private function redirect(string|null $returnTo): void
    {
        // Post/Redirect/Get: a browser follows the safe Location with 303;
        // JSON/Resource clients keep the acknowledgement body with 200 OK.
        ($this->mutationResponse)($this, Code::OK, $this->safeReturnTo($returnTo));
        $this->body = ['message' => '操作を受け付けました。'];
    }

    private function safeReturnTo(string|null $returnTo): string
    {
        if ($returnTo !== null && str_starts_with($returnTo, '/') && ! str_starts_with($returnTo, '//')) {
            return $returnTo;
        }

        return '/';
    }
}
