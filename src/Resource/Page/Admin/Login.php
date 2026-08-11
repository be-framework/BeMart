<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use Be\Framework\SemanticVariable\ValidationMessageHandler;
use MyVendor\BeMart\Auth\HtmlAdminLoginChallengeAdapter;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Be\Exception\AdminLoginFailedException;
use MyVendor\BeMart\Be\Exception\PasswordFormatException;
use MyVendor\BeMart\Be\Final\AdminAuthenticated;
use MyVendor\BeMart\Be\Input\AdminLoginInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface;
use MyVendor\BeMart\Form\AdminLoginForm;
use MyVendor\BeMart\Support\Resource\AdminLoginFormSubmissionInterface;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function array_values;
use function assert;
use function trim;

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
 * Password verification now establishes only a pre-auth 2FA login
 * challenge. The flat admin session key read by
 * {@see HtmlAdminSessionAdapter} is written after the existing-device
 * challenge or first-device setup succeeds, so login-context 2FA
 * resources never need to trust client-supplied identity.
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
    private const POC_LOGIN_PASSWORD = 'admin-test-password-2026';

    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfToken $csrf,
        private readonly FormFactory $formFactory,
        private readonly TwoFactorAuthInterface $twoFactorAuth,
        private readonly HtmlAdminLoginChallengeAdapter $loginChallenge,
        private readonly AdminLoginFormSubmissionInterface $formSubmission,
    ) {
    }

    /**
     * Show the admin login form scaffolding.
     *
     * Pure form-info endpoint: no Be Framework involved, no domain
     * logic. Anonymous-accessible (returns 200 regardless of session
     * state) — the admin firewall guard is on the POST. The `csrfToken`
     * body field carries the trusted reference {@see CsrfToken::$token}
     * issues, which the HTML port renders into the form's hidden
     * `_csrf_token` input so the subsequent POST passes CSRF validation.
     */
    #[Alps('doAdminLogin')]
    #[JsonSchema(schema: 'get-admin-login.json')]
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
            'csrfToken' => $this->csrf->token,
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
     */
    #[Alps('doAdminLogin')]
    #[JsonSchema(schema: 'post-admin-login.json', params: 'post-admin-login.param.json')]
    #[Link(rel: 'goAdminTop', href: 'page://self/admin/index')]
    #[CsrfProtected]
    public function onPost(string|null $loginId = null, string|null $password = null, string|null $mode = null): static
    {
        $values = [
            'loginId' => $loginId ?? '',
            'password' => $password ?? '',
        ];
        $browserForm = ($this->formSubmission)($mode);
        if ($browserForm) {
            $errors = $this->formErrors($values);
            if ($errors !== []) {
                return $this->rejectForm($values, $errors);
            }
        }

        try {
            $final = ($this->becoming)(new AdminLoginInput(
                loginId: $values['loginId'],
                password: $values['password'],
            ));
        } catch (SemanticVariableException $e) {
            if (! $browserForm) {
                throw $e;
            }

            [$field, $message] = self::semanticError($e);

            return $this->rejectForm($values, [$field => $message]);
        } catch (AdminLoginFailedException $e) {
            if (! $browserForm) {
                throw $e;
            }

            return $this->rejectForm(
                $values,
                ['loginId' => self::domainMessage($e)],
                Code::UNAUTHORIZED,
            );
        }

        assert($final instanceof AdminAuthenticated);

        $this->loginChallenge->regenerateActiveSessionId();
        if ($this->twoFactorAuth->isEnabled($final->loginId)) {
            $this->loginChallenge->startVerification($final->adminId, $final->loginId);
            $location = '/admin/two-factor-auth';
        } else {
            $this->loginChallenge->startSetup(
                $final->adminId,
                $final->loginId,
                $this->twoFactorAuth->generateSecret(),
            );
            $location = '/admin/two-factor-auth-set';
        }

        // Post/Redirect/Get: a successful password check redirects to the
        // next login-context 2FA step. JSON clients still read the
        // authenticated admin proof off the body below, but the trusted
        // identity used by 2FA is the session-backed challenge above.
        $this->headers['Location'] = $location;
        $this->body = [
            'adminId' => $final->adminId,
            'loginId' => $final->loginId,
            'name' => $final->name,
            'authority' => $final->authority,
        ];
        if ($mode !== null) {
            // Browser form submit: 303 See Other so the browser actually
            // navigates (a 200 + Location response leaves browsers on the
            // login page). JSON/Resource clients keep 200 OK with the body.
            $this->code = Code::SEE_OTHER;

            return $this;
        }

        $this->code = Code::OK;

        return $this;
    }

    /** @param array{loginId: string, password: string} $values */
    private function formErrors(array $values): array
    {
        $errors = [];
        foreach ([
            'loginId' => '入力してください。',
            'password' => '入力してください。',
        ] as $field => $message) {
            if (trim($values[$field]) === '') {
                $errors[$field] = $message;
            }
        }

        return $errors;
    }

    /** @param array{loginId: string, password: string} $values */
    private function rejectForm(array $values, array $errors, int $code = Code::BAD_REQUEST): static
    {
        $this->code = $code;
        $this->body = [
            'transitionId' => 'goAdminLogin',
            'fields' => ['loginId', 'password', 'csrfToken'],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/admin/login',
            ],
            'csrfToken' => $this->csrf->token,
            'message' => array_values($errors)[0] ?? '入力内容を確認してください。',
            'errors' => $errors,
            'form' => $this->failedForm($values, $errors),
        ];

        return $this;
    }

    /** @param array{loginId: string, password: string} $values */
    private function failedForm(array $values, array $errors): AdminLoginForm
    {
        $form = $this->formFactory->newInstance(AdminLoginForm::class);
        assert($form instanceof AdminLoginForm);

        $form->fillValues(['loginId' => $values['loginId']]);
        foreach ($errors as $field => $message) {
            $form->setDomainError($field, $message);
        }

        return $form;
    }

    /** @return array{0: string, 1: string} */
    private static function semanticError(SemanticVariableException $e): array
    {
        $exception = $e->getErrors()->exceptions[0] ?? null;
        $message = $e->getErrors()->getMessages('ja')[0] ?? '入力内容を確認してください。';

        $field = $exception instanceof PasswordFormatException ? 'password' : 'loginId';

        return [$field, $message];
    }

    private static function domainMessage(AdminLoginFailedException $e): string
    {
        $message = (new ValidationMessageHandler())->getMessage($e, 'ja');

        return $message !== '' && $message !== 'Validation error'
            ? $message
            : 'ログインIDまたはパスワードが正しくありません。';
    }

    private function prefilledLoginForm(): AdminLoginForm
    {
        $form = $this->formFactory->newInstance(AdminLoginForm::class);
        assert($form instanceof AdminLoginForm);

        $form->fillValues([
            'loginId' => self::POC_LOGIN_ID,
            'password' => self::POC_LOGIN_PASSWORD,
        ]);

        return $form;
    }
}
