<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Entry;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\SecretKeyNotFoundException;
use MyVendor\BeMart\Be\Final\CustomerActivated;
use MyVendor\BeMart\Be\Input\ActivateCustomerInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;
use function sprintf;

/**
 * EC-CUBE doActivateCustomer — provisional → active (Pilot 7).
 *
 * The email-link UX in EC-CUBE is GET, but the operation has side
 * effects (status flip + secretKey clear) so the Be migration uses
 * onPost behind a one-button confirmation form. Both the secretKey
 * and a CSRF token are submitted; the secretKey is the per-customer
 * proof-of-email-receipt, and the CSRF token guards against drive-by
 * activation triggered by another origin.
 *
 * Failure mapping:
 *   - SemanticVariableException    → 400 (secretKey malformed)
 *   - SecretKeyNotFoundException   → 404 (wrong key / expired / already used)
 *
 * Idempotent: re-activating a customer is a no-op on the storage side
 * but still redirects from this resource — the caller cannot tell
 * "first activate" from "second activate", which is correct.
 *
 * Phase 3 — `onGet` is the email-verification-complete LANDING SCREEN.
 * EC-CUBE's `doActivateCustomer` controller renders `Entry/activate.twig`
 * (the "本登録が完了しました" page) after the status flip; `onPost`
 * performs the flip. The `onGet` here is a THIN PURE RENDERER for that
 * landing screen — no Be Framework, no domain logic — added so Phase 3
 * has a page to render `Entry/activate.twig` against. The template's
 * optional `{% if qtyInCart %}` cart button is gated behind a cart-state
 * field the thin-renderer body does not carry; the common case (no
 * pending cart) renders only the top-page button, recorded as a residual
 * in the render test.
 */
class Activate extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * EC-CUBE doActivateCustomer landing — the email-verification-complete
     * screen. Pure renderer: the body surfaces only the screen shape + the
     * outbound `goTop` transition (ALPS `#CustomerActivationComplete`).
     */
    #[Alps('goTop')]
    #[JsonSchema(schema: 'get-entry-activate.json')]
    #[Link(rel: 'goTop', href: 'page://self/')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goCustomerActivationComplete',
            'fields' => [],
            'submitTo' => null,
            'staticContent' => [
                'page' => 'entry-activate',
                'title' => '新規会員登録(完了)',
            ],
            'links' => [
                'goTop' => 'page://self/',
            ],
        ];

        return $this;
    }

    /**
     * ALPS `doActivateCustomer` に対応する POST 操作。
     * @psalm-taint-source input $secretKey
     */
    #[Alps('doActivateCustomer')]
    #[JsonSchema(schema: 'post-entry-activate.json', params: 'post-entry-activate.param.json')]
    #[Link(rel: 'goLogin', href: 'page://self/login')]
    #[CsrfProtected]
    public function onPost(string $secretKey): static
    {
        $final = ($this->becoming)(new ActivateCustomerInput(secretKey: $secretKey));

        assert($final instanceof CustomerActivated);

        $this->code = Code::SEE_OTHER;
        $this->headers['Location'] = sprintf('/customer/%s', $final->customerId);
        $this->body = [
            'customerId' => $final->customerId,
            'email' => $final->email,
            'customerStatus' => $final->customerStatus,
        ];

        return $this;
    }
}
