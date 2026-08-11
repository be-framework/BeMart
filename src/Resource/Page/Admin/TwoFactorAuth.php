<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Auth\AdminTwoFactorChallenge;
use MyVendor\BeMart\Auth\HtmlAdminLoginChallengeAdapter;
use MyVendor\BeMart\Be\Exception\TwoFactorAuthFailedException;
use MyVendor\BeMart\Be\Final\TwoFactorAuthVerified;
use MyVendor\BeMart\Be\Input\VerifyTwoFactorAuthInput;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Form\AdminTwoFactorAuthForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE admin 2段階認証 (challenge) — top-level wave, Phase 3.
 *
 * Thin renderer for the admin 2FA challenge screen
 * (`admin/two_factor_auth.twig`, extends `login_frame.twig`). This is a
 * LOGIN-CONTEXT page: it is reached AFTER correct credentials but
 * BEFORE the admin session is fully established, so — like the admin
 * login page — it is anonymous-accessible (no admin-firewall guard).
 *
 * EC-CUBE's controller verifies the submitted TOTP token against the
 * member's stored secret. BeMart binds the member identity to a
 * session-backed pre-auth login challenge, so the submitted form only
 * supplies the device token.
 *
 * Hard ActionRedirect completion: `onPost` now drives the Be
 * `doVerifyTwoFactorAuth` transition ({@see VerifyTwoFactorAuthInput} →
 * {@see TwoFactorAuthVerified}) — the TOTP code is verified against the
 * member's stored secret behind the
 * {@see \MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface} boundary.
 */
class TwoFactorAuth extends ResourceObject
{
    public function __construct(
        private readonly FormFactory $formFactory,
        private readonly BecomingInterface $becoming,
        private readonly HtmlAdminLoginChallengeAdapter $loginChallenge,
        private readonly AdminSession $adminSession,
        private readonly AdminQueryInterface $adminQuery,
        private readonly CsrfToken $csrf,
    ) {
    }

    /**
     * Renders the admin 2FA challenge form.
     *
     * Anonymous-accessible (login-context): returns 200 regardless of
     * session state — the admin firewall guard is downstream of a
     * successful challenge.
     */
    #[Alps('doVerifyTwoFactorAuth')]
    #[JsonSchema(schema: 'get-admin-two-factor-auth.json')]
    #[Link(rel: 'doVerifyTwoFactorAuth', href: 'page://self/admin/two-factor-auth', method: 'post')]
    #[Link(rel: 'goAdminLogin', href: 'page://self/admin/login')]
    public function onGet(): static
    {
        $this->verificationChallenge();

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goAdminTwoFactorAuth',
            'fields' => ['deviceToken', 'csrfToken'],
            'csrfToken' => $this->csrf->token,
            // Phase 3: an empty AdminTwoFactorAuthForm for the HTML port.
            'form' => $this->formFactory->newInstance(AdminTwoFactorAuthForm::class),
        ];
        assert($this->body['form'] instanceof AdminTwoFactorAuthForm);

        return $this;
    }

    private function verificationChallenge(): AdminTwoFactorChallenge|null
    {
        $challenge = $this->loginChallenge->verificationChallenge();
        if ($challenge !== null) {
            return $challenge;
        }

        if ($this->adminSession->adminId === null) {
            return null;
        }

        $admin = $this->adminQuery->item($this->adminSession->adminId);
        if ($admin === null) {
            return null;
        }

        $this->loginChallenge->startVerification($admin->adminId, $admin->loginId);

        return $this->loginChallenge->verificationChallenge();
    }

    /**
     * Verifies the submitted TOTP code (doVerifyTwoFactorAuth).
     *
     * Login-context: no admin-firewall guard. The trusted `loginId` is
     * read from the password-verified session challenge and the admin
     * session is elevated only after the token succeeds. Legacy
     * client-supplied `loginId` is ignored.
     *
     * Failure mapping:
     *   - Invalid CSRF                  → 403 (interceptor)
     *   - Missing pending challenge     → 403
     *   - SemanticVariableException     → 400 (malformed code)
     *   - TwoFactorAuthFailedException  → 400 (code mismatch)
     *
     * @psalm-taint-source input $deviceToken
     */
    #[Alps('doVerifyTwoFactorAuth')]
    #[JsonSchema(schema: 'post-admin-two-factor-auth.json', params: 'post-admin-two-factor-auth.param.json')]
    #[CsrfProtected]
    #[Link(rel: 'goContentCache', href: 'page://self/admin/content/cache')]
    #[Link(rel: 'goAdminHome', href: 'page://self/admin/index')]
    public function onPost(string $deviceToken, string|null $loginId = null, string|null $mode = null): static
    {
        unset($loginId);

        $challenge = $this->loginChallenge->verificationChallenge();
        if ($challenge === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => '二要素認証のログインチャレンジがありません。'];

            return $this;
        }

        $final = ($this->becoming)(new VerifyTwoFactorAuthInput(
            loginId: $challenge->loginId,
            deviceToken: $deviceToken,
        ));

        assert($final instanceof TwoFactorAuthVerified);
        $this->loginChallenge->completeVerification($challenge);

        $this->headers['Location'] = '/admin/index';
        $this->body = [
            'transitionId' => 'doVerifyTwoFactorAuth',
            'loginId' => $final->loginId,
            'message' => '二要素認証を確認しました。',
        ];
        if ($mode !== null) {
            // Browser form submit: 303 See Other so the browser actually
            // navigates (a 200 + Location response leaves browsers on the
            // challenge page). JSON/Resource clients keep 200 OK.
            $this->code = Code::SEE_OTHER;

            return $this;
        }

        $this->code = Code::OK;

        return $this;
    }
}
