<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\AdminLoginFailedException;
use MyVendor\BeMart\Be\Final\AdminAuthenticated;
use MyVendor\BeMart\Be\Input\AdminLoginInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;
use MyVendor\BeMart\Form\AdminLoginForm;
use Ray\WebFormModule\FormFactory;

use function assert;
use function sprintf;

/**
 * EC-CUBE doAdminLogin — 管理者ログイン (Wave 4).
 *
 * Resource is the HTTP entry point: builds AdminLoginInput, hands it
 * to Becoming, and on success returns the authenticated adminId. The
 * Be layer pattern is Direct (Input → Final) — see AdminLoginInput.
 *
 * Failure mapping:
 *   - SemanticVariableException     → 400 (loginId/password format invalid)
 *   - AdminLoginFailedException     → 401 (no such loginId OR wrong
 *                                            password — combined, no
 *                                            user enumeration)
 *
 * Mirrors Pilot 6 customer {@see \MyVendor\BeMart\Resource\Page\Login}
 * but for the admin firewall — distinct namespace under `Page\Admin\`
 * (different URI prefix `page://self/admin/login`), and the response
 * body carries admin shape (adminId / loginId / name / authority)
 * rather than customer shape.
 *
 * Session-write deliberately out of scope: the Slice 7.2 contract
 * places HTTP session establishment on the EC-CUBE EventListener.
 * This resource only returns the proof of authentication; the
 * EventListener mirrors `adminId` into the admin firewall's session
 * keys after observing this 200 response.
 *
 * Source-of-truth gap: alps.json does not currently carry a
 * `doAdminLogin` transition id (only customer `doLogin`). Using the
 * conventional name to parallel the customer side; ALPS profile is
 * expected to gain a matching transition in a later sweep.
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
     * Show the admin login form scaffolding.
     *
     * Pure form-info endpoint: no Be Framework involved, no domain
     * logic. Anonymous-accessible (returns 200 regardless of session
     * state) — the admin firewall guard is on the POST. The
     * `csrfToken` body field is intentionally `null`: CsrfTokenInterface
     * stays `isValid()`-only (Slice 8 decision); production
     * EventListener mirrors the live Symfony-issued token into the
     * session for the subsequent POST.
     */
    #[Link(rel: 'doAdminLogin', href: 'page://self/admin/login', method: 'post')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goAdminLogin',
            'fields' => ['loginId', 'password', 'csrfToken'],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/admin/login',
            ],
            'csrfToken' => null,
            // Phase 3: an empty AdminLoginForm for the HTML port to
            // render via `{{ form.input(...) }}`. JSON contexts ignore it.
            'form' => $this->formFactory->newInstance(AdminLoginForm::class),
        ];

        return $this;
    }

    /**
     * Wave 4 / Phase B Slice 9: every form field is user-controlled
     * input — same taint discipline as the customer login.
     *
     * @psalm-taint-source input $loginId
     * @psalm-taint-source input $password
     * @psalm-taint-source input $csrfToken
     */
    public function onPost(string $loginId, string $password, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new AdminLoginInput(
                loginId: $loginId,
                password: $password,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.',
                'loginId' => $loginId,
            ];

            return $this;
        } catch (AdminLoginFailedException) {
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'ログインIDまたはパスワードが正しくありません。'];

            return $this;
        }

        assert($final instanceof AdminAuthenticated);

        $this->code = Code::OK;
        $this->headers['Location'] = sprintf('/admin/%s', $final->adminId);
        $this->body = [
            'adminId' => $final->adminId,
            'loginId' => $final->loginId,
            'name' => $final->name,
            'authority' => $final->authority,
        ];

        return $this;
    }
}
