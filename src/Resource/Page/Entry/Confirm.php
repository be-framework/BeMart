<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Entry;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Form\EntryConfirmForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE goCustomerRegistrationConfirm — 新規会員登録(確認)
 * (Phase 3 — thin pure renderer).
 *
 * NEW RESOURCE — flagged as a follow-up. EC-CUBE's registration flow has
 * an `Entry::confirm` step between `goCustomerRegistration` (the form)
 * and `doRegisterCustomer` (the commit): the customer reviews the entered
 * values before the account is created. EC-CUBE keeps that step on the
 * SAME controller action, branching on the `mode` POST param
 * (`confirm` / `complete` / `back`); BeMart's Pilot 4 collapsed the flow
 * — `Entry::onGet` (form) hands straight to `Entry::onPost`
 * (doRegisterCustomer) — so no `CustomerRegistrationConfirm` resource
 * existed. Phase 3 needs a page to render `Entry/confirm.twig` against,
 * so this THIN PURE RENDERER is added: no Be Framework, no domain logic,
 * no Reasons.
 *
 * FORM page (the form-page recipe — see var/templates/README.md). The
 * confirm screen re-shows the entered registration values as plain text
 * AND carries them forward as HIDDEN inputs so the final submit re-posts
 * the full payload to `doRegisterCustomer`. The resource exposes an
 * {@see EntryConfirmForm} (every registration field declared `hidden`)
 * as `body['form']` so the HTML port renders the hidden carriers via
 * `{{ form.input(...) }}`.
 *
 * FOLLOW-UP — the confirm screen's plain-text value cells show the
 * entered registration data; a pure `onGet` renderer has no submitted
 * values, so those cells render empty (the body carries no field
 * values). Threading the submitted payload into the confirm step — a
 * real `mode=confirm` POST handler that re-shows the values before the
 * commit — is a dedicated vertical slice, tracked in the enrichment
 * backlog. Recorded as a MISSING BODY FIELD residual in the render test.
 *
 * Maps to `page://self/entry/confirm`. The submit target is
 * doRegisterCustomer (`page://self/entry`).
 */
class Confirm extends ResourceObject
{
    public function __construct(
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * EC-CUBE goCustomerRegistrationConfirm — render the registration-confirm
     * (review) screen.
     *
     * The registration values are optional GET params: when the confirm step
     * is reached from a `mode=confirm` POST (see
     * {@see \MyVendor\BeMart\Resource\Page\Entry::onPost}), the entered
     * registration payload is threaded in so the plain-text value cells re-show
     * the submitted data AND the hidden EntryConfirmForm carriers repopulate,
     * so the final 会員登録をする submit (`mode=complete`) re-posts the full
     * payload to doRegisterCustomer. A bare GET (no values) renders the empty
     * confirm scaffolding — JSON contexts ignore `body['form']`.
     */
    #[Alps('goCustomerRegistrationConfirm')]
    #[JsonSchema(schema: 'get-entry-confirm.json')]
    #[Link(rel: 'doRegisterCustomer', href: 'page://self/entry', method: 'post')]
    #[Link(rel: 'goTop', href: 'page://self/')]
    public function onGet(
        string|null $name01 = null,
        string|null $name02 = null,
        string|null $kana01 = null,
        string|null $kana02 = null,
        string|null $companyName = null,
        string|null $postalCode = null,
        string|null $pref = null,
        string|null $addr01 = null,
        string|null $addr02 = null,
        string|null $phoneNumber = null,
        string|null $email = null,
        string|null $email_confirm = null,
        string|null $password = null,
        string|null $password_confirm = null,
        string|null $birth = null,
        string|null $birth_year = null,
        string|null $birth_month = null,
        string|null $birth_day = null,
        string|null $sex = null,
        string|null $job = null,
        string|null $user_policy_check = null,
    ): static {
        $values = [
            'name01' => $name01 ?? '',
            'name02' => $name02 ?? '',
            'kana01' => $kana01 ?? '',
            'kana02' => $kana02 ?? '',
            'companyName' => $companyName ?? '',
            'postalCode' => $postalCode ?? '',
            'pref' => $pref ?? '',
            'addr01' => $addr01 ?? '',
            'addr02' => $addr02 ?? '',
            'phoneNumber' => $phoneNumber ?? '',
            'email' => $email ?? '',
            'email_confirm' => $email_confirm ?? '',
            'password' => $password ?? '',
            'password_confirm' => $password_confirm ?? '',
            'birth_year' => $birth_year ?? '',
            'birth_month' => $birth_month ?? '',
            'birth_day' => $birth_day ?? '',
            'sex' => $sex ?? '',
            'job' => $job ?? '',
            'user_policy_check' => $user_policy_check ?? '',
        ];

        $form = $this->formFactory->newInstance(EntryConfirmForm::class);
        assert($form instanceof EntryConfirmForm);
        $form->fillValues($values);

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goCustomerRegistrationConfirm',
            'fields' => [],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/entry',
            ],
            'staticContent' => [
                'page' => 'entry-confirm',
                'title' => '新規会員登録(確認)',
            ],
            // Plain-text value cells re-show the entered registration data.
            'name01' => $values['name01'],
            'name02' => $values['name02'],
            'kana01' => $values['kana01'],
            'kana02' => $values['kana02'],
            'companyName' => $values['companyName'],
            'postalCode' => $values['postalCode'],
            'pref' => $values['pref'],
            'addr01' => $values['addr01'],
            'addr02' => $values['addr02'],
            'phoneNumber' => $values['phoneNumber'],
            'email' => $values['email'],
            'birth' => $birth ?? '',
            'sex' => $values['sex'],
            'job' => $values['job'],
            // The confirm screen carries the registration payload as hidden
            // inputs — an EntryConfirmForm (every field `hidden`), repopulated
            // with the entered values. JSON contexts ignore `body['form']`.
            'form' => $form,
        ];

        return $this;
    }
}
