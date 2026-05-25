<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\LoginFailedException;
use MyVendor\BeMart\Be\Final\CustomerAuthenticated;
use MyVendor\BeMart\Be\Input\LoginInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;
use MyVendor\BeMart\Form\LoginForm;
use Ray\WebFormModule\FormFactory;

use function assert;
use function sprintf;

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
 * Session-write deliberately out of scope: the Slice 7.2 contract
 * places HTTP session establishment on the EC-CUBE EventListener.
 * This resource only returns the proof of authentication; routing
 * downstream requests through the session is the EventListener's
 * responsibility once Phase B Slice 7.x lands. For test purposes,
 * AppModule binds a fixed-customer FakeSession.
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
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * EC-CUBE goLogin — show the login form scaffolding.
     *
     * Pure form-info endpoint: no Be Framework involved, no domain
     * logic. Anonymous-accessible (returns 200 regardless of session
     * state). The `csrfToken` body field is intentionally `null` —
     * CsrfTokenInterface stays `isValid()`-only (Slice 8 decision);
     * the EC-CUBE EventListener mirrors the Symfony-issued token
     * into the session for the subsequent POST.
     */
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
            'csrfToken' => null,
            // Phase 3: an empty LoginForm for the HTML port to render
            // via `{{ form.input(...) }}`. JSON contexts ignore it.
            'form' => $this->formFactory->newInstance(LoginForm::class),
        ];

        return $this;
    }

    /**
     * Phase B Slice 9: every form field is user-controlled input.
     *
     * @psalm-taint-source input $email
     * @psalm-taint-source input $password
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goMypage', href: 'page://self/mypage')]
    public function onPost(string $email, string $password, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new LoginInput(
                email: $email,
                password: $password,
            ));
        } catch (SemanticVariableException $e) {
            // Be Framework Semantics rejected the input shape (malformed
            // email / out-of-range password). Bridge the domain verdict
            // onto the form: repopulate the email, attach the ja message
            // as an inline field error. EC-CUBE shows this in
            // `ec-errorMessage`.
            $message = $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.';
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $message,
                'email' => $email,
                'form' => $this->failedForm($email, $message),
            ];

            return $this;
        } catch (LoginFailedException) {
            // Wrong credentials. The top-level body deliberately does NOT
            // echo the email — that would leak user-enumeration signal.
            // The repopulated email lives INSIDE `body['form']` only, so
            // the HTML page re-shows it (EC-CUBE's getLastUsername UX)
            // while the JSON body stays enumeration-safe.
            $message = 'メールアドレスまたはパスワードが正しくありません。';
            $this->code = Code::UNAUTHORIZED;
            $this->body = [
                'message' => $message,
                'form' => $this->failedForm($email, $message),
            ];

            return $this;
        }

        assert($final instanceof CustomerAuthenticated);

        $this->code = Code::OK;
        $this->headers['Location'] = sprintf('/mypage/%s', $final->customerId);
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
        $form->fillValues(['login_email' => $email]);
        // Bridge the Be-domain verdict onto the form's error state.
        $form->setDomainError('login_email', $message);

        return $form;
    }
}
