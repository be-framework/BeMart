<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Mypage;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\CustomerUpdated;
use MyVendor\BeMart\Be\Final\MypageChangeFormFetched;
use MyVendor\BeMart\Be\Input\GetMypageChangeInput;
use MyVendor\BeMart\Be\Input\UpdateCustomerInput;
use MyVendor\BeMart\Form\ChangeForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function array_filter;
use function assert;

/**
 * EC-CUBE doUpdateCustomer — マイページから会員情報を更新 (Pilot 8).
 *
 * AUTHZ via the Be layer: the customerId for the update target is
 * the CustomerSession's value — never the request body — so an
 * authenticated customer cannot edit another customer's record by
 * tampering with form fields (Pilot 5 F-2 lesson).
 *
 * Failure mapping:
 *   - SemanticVariableException        → 400 (field format invalid)
 *   - UnauthenticatedException         → 401 (no session)
 *   - EmailAlreadyRegisteredException  → 409 (email change collides)
 */
class Change extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * goMypageChange — show the change-customer-info form pre-populated
     * with the logged-in customer's current values.
     *
     * Safe read. No CSRF (read-only). AUTHN in the Be layer maps null
     * session → 401.
     *
     * Phase 3 — HTML FORM page. The resource builds a {@see ChangeForm}
     * (Ray.WebFormModule AbstractForm), pre-populates it with the
     * fetched profile, and exposes it as `body['form']` so the HTML port
     * renders real `<input>`s via `{{ form.input(...) }}`. VALIDATION
     * AUTHORITY STAYS WITH the Be Framework Becoming chain (onPost). The
     * JSON contexts ignore `body['form']`; the flat profile keys stay on
     * `body` for the JSON-context tests.
     */
    #[Alps('goMypageChange')]
    #[JsonSchema(schema: 'get-mypage-change.json')]
    #[Link(rel: 'goMypage', href: 'page://self/mypage')]
    public function onGet(): static
    {
        $final = ($this->becoming)(new GetMypageChangeInput());

        assert($final instanceof MypageChangeFormFetched);

        $form = $this->formFactory->newInstance(ChangeForm::class);
        assert($form instanceof ChangeForm);
        $form->fillValues(array_filter([
            'name01' => $final->name01,
            'name02' => $final->name02,
            'kana01' => $final->kana01,
            'kana02' => $final->kana02,
            'companyName' => $final->companyName,
            'postalCode' => $final->postalCode,
            'pref' => $final->pref,
            'addr01' => $final->addr01,
            'addr02' => $final->addr02,
            'phoneNumber' => $final->phoneNumber,
            'email' => $final->email,
        ], static fn (mixed $v): bool => $v !== null));

        $this->code = Code::OK;
        $this->body = [
            'customerId' => $final->customerId,
            'email' => $final->email,
            'name01' => $final->name01,
            'name02' => $final->name02,
            'kana01' => $final->kana01,
            'kana02' => $final->kana02,
            'companyName' => $final->companyName,
            'phoneNumber' => $final->phoneNumber,
            'postalCode' => $final->postalCode,
            'pref' => $final->pref,
            'addr01' => $final->addr01,
            'addr02' => $final->addr02,
            'submitTo' => $final->submitTo,
            // Phase 3: a ChangeForm pre-populated with the current
            // profile for the HTML port. JSON contexts ignore it.
            'form' => $form,
        ];

        return $this;
    }

    /**
     * ALPS `doUpdateCustomer` に対応する POST 操作。
     * @psalm-taint-source input $email
     * @psalm-taint-source input $name01
     * @psalm-taint-source input $name02
     * @psalm-taint-source input $kana01
     * @psalm-taint-source input $kana02
     * @psalm-taint-source input $companyName
     * @psalm-taint-source input $phoneNumber
     * @psalm-taint-source input $postalCode
     * @psalm-taint-source input $pref
     * @psalm-taint-source input $addr01
     * @psalm-taint-source input $addr02
     */
    #[Alps('doUpdateCustomer')]
    #[JsonSchema(schema: 'post-mypage-change.json', params: 'post-mypage-change.param.json')]
    #[Link(rel: 'goMypageChangeComplete', href: 'page://self/mypage/change-complete')]
    #[Link(rel: 'goMypage', href: 'page://self/mypage')]
    #[CsrfProtected]
    public function onPost(
        string $email,
        string|null $name01 = null,
        string|null $name02 = null,
        string|null $kana01 = null,
        string|null $kana02 = null,
        string|null $companyName = null,
        string|null $phoneNumber = null,
        string|null $postalCode = null,
        int|null $pref = null,
        string|null $addr01 = null,
        string|null $addr02 = null,
            string|null $csrfToken = null,
    ): static {
        $final = ($this->becoming)(new UpdateCustomerInput(
            email: $email,
            name01: $name01,
            name02: $name02,
            kana01: $kana01,
            kana02: $kana02,
            companyName: $companyName,
            phoneNumber: $phoneNumber,
            postalCode: $postalCode,
            pref: $pref,
            addr01: $addr01,
            addr02: $addr02,
        ));

        assert($final instanceof CustomerUpdated);

        $this->code = Code::OK;
        $this->body = [
            'customerId' => $final->customerId,
            'email' => $final->email,
            'name01' => $final->name01,
            'name02' => $final->name02,
        ];

        return $this;
    }
}
