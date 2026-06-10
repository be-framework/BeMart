<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Auth\CustomerSessionWriterInterface;
use MyVendor\BeMart\Be\Exception\LoginFailedException;
use MyVendor\BeMart\Be\Final\CustomerAuthenticated;
use MyVendor\BeMart\Be\Input\LoginInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Form\LoginForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE doLogin — 会員ログイン (Pilot 6).
 *
 * Resource is the HTTP entry point: builds LoginInput, hands it to
 * Becoming, and on success returns the authenticated customerId. The
 * Be layer pattern is Direct (Input → Final) — see LoginInput.
 *
 * Failure mapping:
 *   - SemanticVariableException → 400 (email/password format invalid)
 *   - LoginFailedException      → 401 (no such email OR wrong password
 *                                       — combined, no user enumeration)
 *
 * In the html context, the context module binds a session writer that
 * mirrors `customerId` into the flat session key read by the HTML session
 * adapter. Non-html contexts bind a no-op writer, so Resource code does
 * not branch on environment or touch PHP session storage directly.
 *
 * Phase 3 — HTML FORM page. The resource builds a {@see LoginForm}
 * (Ray.WebFormModule AbstractForm) and exposes it as `body['form']` so
 * the HTML port can render real `<input>`s via `{{ form.input(...) }}`.
 * The form is a field-definition + renderer only — VALIDATION AUTHORITY
 * STAYS WITH the Be Framework Becoming chain. On a domain rejection the
 * resource bridges the verdict onto the form (repopulated email + inline
 * error) so the Login page re-renders with EC-CUBE's exact form UX. The
 * JSON contexts (`app`, `prod`, `test`) ignore `body['form']`; the 1445
 * JSON-context tests assert key-wise on `body` and are unaffected.
 *
 * FormFactory is self-sufficient (no Ray.Di bindings needed), so the
 * resource builds the form in every context cheaply; only the `html`
 * context's TwigRenderer actually renders it.
 */
class Login extends ResourceObject
{
    // PoC fixture prefill for the browser demo. Remove these constants
    // and the prefilledLoginForm() call before production hardening.
    private const POC_LOGIN_EMAIL = 'login-test@example.com';
    private const POC_LOGIN_PASSWORD = 'login-test-password-2026';

    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfToken $csrf,
        private readonly FormFactory $formFactory,
        private readonly CustomerSessionWriterInterface $sessionWriter,
    ) {
    }

    /**
     * EC-CUBE goLogin — show the login form scaffolding.
     *
     * Pure form-info endpoint: no Be Framework involved, no domain
     * logic. Anonymous-accessible (returns 200 regardless of session
     * state). The `csrfToken` body field carries the trusted reference
     * {@see CsrfToken::$token} issues, which the HTML port
     * renders into the form's hidden `_csrf_token` input so the
     * subsequent POST passes CSRF validation.
     */
    #[Alps('goLogin')]
    #[JsonSchema(schema: 'get-login.json')]
    #[Link(rel: 'doLogin', href: 'page://self/login', method: 'post')]
    #[Link(rel: 'goCustomerRegistration', href: 'page://self/entry')]
    #[Link(rel: 'doRequestPasswordReset', href: 'page://self/forgot-password', method: 'post')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goLogin',
            'fields' => ['email', 'password', 'csrfToken'],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/login',
            ],
            'csrfToken' => $this->csrf->token,
            // PoC fixture prefill for quick HTML-context verification.
            // See prefilledLoginForm(); deliberately easy to remove.
            'form' => $this->prefilledLoginForm(),
        ];

        return $this;
    }

    /**
     * Phase B Slice 9: every form field is user-controlled input.
     *
     * @psalm-taint-source input $email
     * @psalm-taint-source input $password
     */
    #[Alps('doLogin')]
    #[JsonSchema(schema: 'post-login.json', params: 'post-login.param.json')]
    #[Link(rel: 'goMypage', href: 'page://self/mypage')]
    #[CsrfProtected]
    public function onPost(string $email, string $password): static
    {
        $final = ($this->becoming)(new LoginInput(
            email: $email,
            password: $password,
        ));

        assert($final instanceof CustomerAuthenticated);

        $this->sessionWriter->authenticate($final->customerId);

        // Post/Redirect/Get: a successful login redirects to My Page.
        // EC-CUBE's doLogin redirects to the `mypage` route (`/mypage`) —
        // My Page reads the authenticated customer from the session, so
        // the customerId is NOT a URL segment (there is no
        // `/mypage/{customerId}` route). JSON clients still read
        // `customerId` off the body below.
        $this->code = Code::OK;
        $this->headers['Location'] = '/mypage';
        $this->body = [
            'customerId' => $final->customerId,
            'email' => $final->email,
            'name01' => $final->name01,
            'name02' => $final->name02,
            'customerStatus' => $final->customerStatus,
        ];

        return $this;
    }

    /**
     * Builds a LoginForm reflecting a rejected POST.
     *
     * The Becoming chain has already reached the verdict; this only
     * transports it onto the form so the HTML page re-renders with the
     * entered email and the inline error. Validation authority remains
     * with Be — the form is a renderer here, never a validator.
     */
    private function failedForm(string $email, string $message): LoginForm
    {
        $form = $this->formFactory->newInstance(LoginForm::class);
        assert($form instanceof LoginForm);

        // Repopulate the email (EC-CUBE getLastUsername UX). The password
        // is deliberately not repopulated.
        $form->fillValues(['email' => $email]);
        // Bridge the Be-domain verdict onto the form's error state.
        $form->setDomainError('email', $message);

        return $form;
    }

    private function prefilledLoginForm(): LoginForm
    {
        $form = $this->formFactory->newInstance(LoginForm::class);
        assert($form instanceof LoginForm);

        $form->fillValues([
            'email' => self::POC_LOGIN_EMAIL,
            'password' => self::POC_LOGIN_PASSWORD,
        ]);

        return $form;
    }
}
