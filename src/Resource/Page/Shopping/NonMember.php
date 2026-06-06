<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Final\NonMemberSubmitted;
use MyVendor\BeMart\Be\Input\SubmitNonMemberInput;
use MyVendor\BeMart\Form\NonMemberForm;
use Ray\WebFormModule\FormFactory;

use function assert;
use function sprintf;

/**
 * EC-CUBE goShoppingNonMember / doSubmitNonMember —非会員購入 (Wave 7W).
 *
 *   onGet  → goShoppingNonMember (safe form-info, anonymous-accessible)
 *   onPost → doSubmitNonMember   (unsafe, Direct, Semantic-validated)
 *
 * Wave 7W is the FORM ENTRY only. The Final intentionally does NOT
 * persist a Cart / PreOrder under the guest's identity, and Pilot 5's
 * doCheckout still requires a customer session — so the preOrderId
 * returned by onPost will currently 403 on the subsequent checkout.
 * Closing that gap is Phase 2's job (dedicated GuestProfile entity +
 * non-member branch in CheckoutPrepared). See NonMemberSubmitted's
 * docblock for the full rationale.
 *
 * Failure mapping (onPost):
 *   - CSRF invalid              → 403 (boundary)
 *   - SemanticVariableException → 400 (any guest field malformed)
 *
 * Coexists with `Resource\Page\Shopping\Checkout.php` (Pilot 5) under
 * the same `Shopping/` directory — the same file-plus-sibling-directory
 * pattern as Mypage / Entry.
 *
 * Phase 3 — HTML FORM page. `Shopping/nonmember.twig` renders the
 * guest-info inputs through the Symfony FormView; BeMart exposes a
 * {@see NonMemberForm} (Ray.WebFormModule AbstractForm) as `body['form']`
 * so the HTML port renders real `<input>`s via `{{ form.input(...) }}`.
 * The form is a field-definition + renderer only — VALIDATION AUTHORITY
 * STAYS WITH the Be Becoming chain (doSubmitNonMember /
 * SubmitNonMemberInput). On a domain rejection the resource bridges the
 * verdict onto the form. JSON contexts ignore `body['form']`.
 */
class NonMember extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * EC-CUBE goShoppingNonMember — show the guest-info entry form.
     *
     * Pure form-info endpoint: no Be Framework involved, no domain
     * logic. Anonymous-accessible (returns 200 regardless of session
     * state). Fields mirror SubmitNonMemberInput. `csrfToken` body
     * field stays `null` for the same reason described on Login::onGet
     * — EventListener mirrors the Symfony token into the session for
     * the subsequent POST.
     */
    #[Link(rel: 'doSubmitNonMember', href: 'page://self/shopping/non-member', method: 'post')]
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goShoppingNonMember',
            'fields' => [
                'name01',
                'name02',
                'kana01',
                'kana02',
                'email',
                'phoneNumber',
                'postalCode',
                'pref',
                'addr01',
                'addr02',
                'csrfToken',
            ],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/shopping/non-member',
            ],
            'csrfToken' => null,
            // Phase 3: an empty NonMemberForm for the HTML port to render
            // the guest-info inputs. JSON contexts ignore it.
            'form' => $this->formFactory->newInstance(NonMemberForm::class),
        ];

        return $this;
    }

    /**
     * EC-CUBE doSubmitNonMember — accept guest shipping info and return
     * the synthesised preOrderId.
     *
     * Phase B Slice 9: every guest form field is user-controlled input.
     * Declared as taint sources so Psalm can trace them downstream.
     * Semantic value objects format-validate but do not universally
     * escape — sinks downstream remain responsible for their own
     * defence (bound params, HTML escape on render).
     *
     * @psalm-taint-source input $name01
     * @psalm-taint-source input $name02
     * @psalm-taint-source input $kana01
     * @psalm-taint-source input $kana02
     * @psalm-taint-source input $email
     * @psalm-taint-source input $phoneNumber
     * @psalm-taint-source input $postalCode
     * @psalm-taint-source input $pref
     * @psalm-taint-source input $addr01
     * @psalm-taint-source input $addr02
     * @psalm-taint-source input $sessionPrefix
     */
    #[Link(rel: 'goShopping', href: 'page://self/shopping')]
    #[CsrfProtected]
    public function onPost(
        string $name01,
        string $name02,
        string $kana01,
        string $kana02,
        string $email,
        string $phoneNumber,
        string $postalCode,
        int $pref,
        string $addr01,
        string $addr02,
        string $sessionPrefix = 'session-prefix-1',
    ): static {
        $final = ($this->becoming)(new SubmitNonMemberInput(
            name01: $name01,
            name02: $name02,
            kana01: $kana01,
            kana02: $kana02,
            email: $email,
            phoneNumber: $phoneNumber,
            postalCode: $postalCode,
            pref: $pref,
            addr01: $addr01,
            addr02: $addr02,
            sessionPrefix: $sessionPrefix,
        ));

        assert($final instanceof NonMemberSubmitted);

        $this->code = Code::CREATED;
        $this->headers['Location'] = sprintf('/shopping?preOrderId=%s', $final->preOrderId);
        $this->body = [
            'preOrderId' => $final->preOrderId,
            'name01' => $final->name01,
            'name02' => $final->name02,
            'email' => $final->email,
        ];

        return $this;
    }
}
