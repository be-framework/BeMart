<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException;
use MyVendor\BeMart\Be\Final\CustomerRegistered;
use MyVendor\BeMart\Be\Input\RegisterCustomerInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Form\EntryForm;
use Ray\WebFormModule\FormFactory;

use function array_filter;
use function assert;
use function bin2hex;
use function random_bytes;
use function substr;

/**
 * EC-CUBE doRegisterCustomer —会員登録 (Entry/Register).
 *
 * Resource is the HTTP entry point: it builds RegisterCustomerInput, hands
 * it to Becoming, and projects the resulting CustomerRegistered into the
 * response body. The 4 required EC-CUBE form fields (email / password /
 * name01 / name02) are positional; the 11 optional fields are passed
 * through unchanged with `null` defaults — see RegisterCustomerInput.
 *
 * Pilot 4 implements the email-verification-OFF flow only
 * (customerStatus = 2 = Active). The OFF path lands on the
 * `CustomerRegistrationComplete` state, whose ALPS surface declares the
 * single transition `goTop`. The verification-ON branch (provisional →
 * email confirm → activate) is deferred to a future Branching pilot.
 *
 * Phase 3 — HTML FORM page. The resource builds an {@see EntryForm}
 * (Ray.WebFormModule AbstractForm) and exposes it as `body['form']` so
 * the HTML port renders real `<input>`s via `{{ form.input(...) }}`. The
 * form is a field-definition + renderer only — VALIDATION AUTHORITY STAYS
 * WITH the Be Framework Becoming chain. On a domain rejection the
 * resource bridges the verdict onto the form (repopulated values + inline
 * error). The JSON contexts (`app`, `prod`, `test`) ignore `body['form']`.
 *
 * @see RegisterCustomerInput  Pilot 4 scope note
 */
class Entry extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfToken $csrf,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * EC-CUBE goCustomerRegistration — show the customer registration
     * form scaffolding.
     *
     * Pure form-info endpoint: no Be Framework involved, no domain
     * logic. Anonymous-accessible (returns 200 regardless of session
     * state). Fields mirror RegisterCustomerInput: 4 required + 11
     * optional. In the dev/html fake-CSRF environment we expose the fake
     * token into the hidden `_token` input so a real browser form submit
     * can exercise the POST path instead of failing at the boundary.
     */
    #[Link(rel: 'doRegisterCustomer', href: 'page://self/entry', method: 'post')]
    public function onGet(): static
    {
        $suggestedEmail = 'entry-' . substr(bin2hex(random_bytes(4)), 0, 8) . '@example.test';
        $form = $this->formFactory->newInstance(EntryForm::class);
        assert($form instanceof EntryForm);
        $form->fillValues([
            'email' => $suggestedEmail,
            'email_confirm' => $suggestedEmail,
        ]);

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goCustomerRegistration',
            'fields' => [
                'email',
                'password',
                'name01',
                'name02',
                'kana01',
                'kana02',
                'companyName',
                'phoneNumber',
                'postalCode',
                'pref',
                'addr01',
                'addr02',
                'birth',
                'sex',
                'job',
                'csrfToken',
            ],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/entry',
            ],
            'csrfToken' => $this->csrfTokenForForm(),
            // Phase 3: an EntryForm for the HTML port to render via
            // `{{ form.input(...) }}`. The demo email is unique so browser
            // smoke submissions exercise the success transition instead of
            // colliding with fixture customers. JSON contexts ignore it.
            'form' => $form,
        ];

        return $this;
    }

    /**
     * Phase B Slice 9: every form field is user-controlled input. Declared
     * as taint sources so Psalm can trace them. Semantic value objects
     * format-validate but do not universally escape — sinks downstream
     * still need their own defense (bound params, HTML escape on render).
     *
     * @psalm-taint-source input $email
     * @psalm-taint-source input $password
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
     * @psalm-taint-source input $birth
     * @psalm-taint-source input $sex
     * @psalm-taint-source input $job
     */
    #[Link(rel: 'goTop', href: 'page://self/')]
    #[CsrfProtected]
    public function onPost(
        string $email,
        string $password,
        string $name01,
        string $name02,
        string|null $kana01 = null,
        string|null $kana02 = null,
        string|null $companyName = null,
        string|null $phoneNumber = null,
        string|null $postalCode = null,
        int|null $pref = null,
        string|null $addr01 = null,
        string|null $addr02 = null,
        string|null $birth = null,
        int|null $sex = null,
        int|null $job = null,
    ): static {
        try {
            $final = ($this->becoming)(new RegisterCustomerInput(
                email: $email,
                password: $password,
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
                birth: $birth,
                sex: $sex,
                job: $job,
            ));
        } catch (SemanticVariableException $e) {
            $message = $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.';
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $message,
                'email' => $email,
                'csrfToken' => $this->csrfTokenForForm(),
                'form' => $this->failedForm($email, $name01, $name02, $message),
            ];

            return $this;
        } catch (EmailAlreadyRegisteredException) {
            // BEAR\Resource\Code lacks CONFLICT; use the integer literal
            // (same convention as Pilot 2's OutOfStockException).
            $message = 'The email is already registered.';
            $this->code = 409;
            $this->body = [
                'message' => $message,
                'email' => $email,
                'csrfToken' => $this->csrfTokenForForm(),
                'form' => $this->failedForm($email, $name01, $name02, $message),
            ];

            return $this;
        }

        assert($final instanceof CustomerRegistered);

        $this->code = Code::CREATED;
        $this->headers['Location'] = '/entry/complete';
        $this->body = [
            'customerId' => $final->customerId,
            'email' => $final->email,
            'name01' => $final->name01,
            'name02' => $final->name02,
            'initialPoint' => $final->initialPoint,
            'customerStatus' => $final->customerStatus,
        ];

        return $this;
    }

    /**
     * Builds an EntryForm reflecting a rejected POST.
     *
     * The Becoming chain has already reached the verdict; this only
     * transports it onto the form so the HTML page re-renders with the
     * entered values and the inline error. Validation authority remains
     * with Be — the form is a renderer here, never a validator.
     */
    private function failedForm(
        string $email,
        string $name01,
        string $name02,
        string $message,
    ): EntryForm {
        $form = $this->formFactory->newInstance(EntryForm::class);
        assert($form instanceof EntryForm);

        // Repopulate the safe-to-echo values (password fields excluded).
        $form->fillValues(array_filter([
            'email' => $email,
            'name01' => $name01,
            'name02' => $name02,
        ], static fn (string $v): bool => $v !== ''));
        // Bridge the Be-domain verdict onto the form's error state.
        $form->setDomainError('email', $message);

        return $form;
    }

    private function csrfTokenForForm(): string
    {
        return $this->csrf->token;
    }
}
