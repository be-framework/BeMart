<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Be\Exception\AdminLoginFailedException;
use MyVendor\BeMart\Be\Final\AdminAuthenticated;
use MyVendor\BeMart\Be\Input\AdminLoginInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;
use MyVendor\BeMart\Form\AdminLoginForm;
use Ray\WebFormModule\FormFactory;

use function assert;
use function getenv;
use function session_status;

use const PHP_SESSION_ACTIVE;

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
 * In the html context, public/index.php starts a PHP session before
 * dispatch and this resource mirrors `adminId` into the flat session
 * key read by HtmlAdminSessionAdapter. The write is guarded by
 * APP_CONTEXT=html and PHP_SESSION_ACTIVE so app/test/prod contexts
 * keep their existing session behaviour and are not polluted by direct
 * `$_SESSION` writes.
 *
 * Source-of-truth gap: alps.json does not currently carry a
 * `doAdminLogin` transition id (only customer `doLogin`). Using the
 * conventional name to parallel the customer side; ALPS profile is
 * expected to gain a matching transition in a later sweep.
 */
class Login extends ResourceObject
{
    // PoC fixture prefill for the browser demo. Remove these constants
    // and the prefilledLoginForm() call before production hardening.
    private const POC_LOGIN_ID = 'test-admin';
    private const POC_LOGIN_PASSWORD = '';

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
     * state) — the admin firewall guard is on the POST. The `csrfToken`
     * body field carries the trusted reference {@see CsrfTokenInterface::getToken()}
     * issues, which the HTML port renders into the form's hidden
     * `_csrf_token` input so the subsequent POST passes CSRF validation.
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
            'csrfToken' => $this->csrf->getToken(),
            // PoC fixture prefill for quick HTML-context verification.
            // See prefilledLoginForm(); deliberately easy to remove.
            'form' => $this->prefilledLoginForm(),
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

        if (getenv('APP_CONTEXT') === 'html' && session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY] = $final->adminId;
        }

        // Post/Redirect/Get: a successful login redirects to the admin
        // dashboard. EC-CUBE's doAdminLogin redirects to the
        // `admin_homepage` route (`/admin`) — the dashboard reads the
        // authenticated admin from the session, so the adminId is NOT a
        // URL segment (there is no `/admin/{adminId}` route). JSON
        // clients still read `adminId` off the body below.
        $this->code = Code::OK;
        $this->headers['Location'] = '/admin';
        $this->body = [
            'adminId' => $final->adminId,
            'loginId' => $final->loginId,
            'name' => $final->name,
            'authority' => $final->authority,
        ];

        return $this;
    }

    private function prefilledLoginForm(): AdminLoginForm
    {
        $form = $this->formFactory->newInstance(AdminLoginForm::class);
        assert($form instanceof AdminLoginForm);

        $form->fillValues([
            'login_id' => self::POC_LOGIN_ID,
            'password' => self::POC_LOGIN_PASSWORD,
        ]);

        return $form;
    }
}
