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
 */
class Login extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
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
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.',
                'email' => $email,
            ];

            return $this;
        } catch (LoginFailedException) {
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'メールアドレスまたはパスワードが正しくありません。'];

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
}
