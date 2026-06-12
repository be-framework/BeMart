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
use MyVendor\BeMart\Be\Final\TwoFactorAuthConfigured;
use MyVendor\BeMart\Be\Input\SetTwoFactorAuthInput;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface;
use MyVendor\BeMart\Form\AdminTwoFactorAuthForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE admin 2段階認証 デバイス登録 — top-level wave, Phase 3.
 *
 * Thin renderer for the admin 2FA device-setup screen
 * (`admin/two_factor_auth_set.twig`, extends `login_frame.twig`). This
 * is a LOGIN-CONTEXT page reached after correct credentials when the
 * member has no 2FA device yet, so — like the admin login page — it is
 * anonymous-accessible (no admin-firewall guard).
 *
 * EC-CUBE's controller generates a TOTP secret server-side, binds it to
 * the pending login identity, renders it as a QR code (the JS in the
 * template builds the `otpauth://` URI) and verifies the first token.
 * BeMart mirrors that boundary through a session-backed login challenge:
 * `onGet` exposes the server-generated secret only when the password
 * step has established pending setup state.
 *
 * Hard ActionRedirect completion: `onPut` drives the Be
 * `doSetTwoFactorAuth` transition ({@see SetTwoFactorAuthInput} →
 * {@see TwoFactorAuthConfigured}) — register the secret, then confirm by
 * verifying the first device code.
 *
 * Without pending setup state `onGet` keeps the empty `authKey`
 * placeholder for render-test fidelity, and `onPut` refuses to configure
 * a device. Client-supplied legacy `loginId` / `authKey` fields are
 * ignored; the trusted identity and secret come only from the challenge.
 */
class TwoFactorAuthSet extends ResourceObject
{
    public function __construct(
        private readonly FormFactory $formFactory,
        private readonly BecomingInterface $becoming,
        private readonly HtmlAdminLoginChallengeAdapter $loginChallenge,
        private readonly AdminSession $adminSession,
        private readonly AdminQueryInterface $adminQuery,
        private readonly TwoFactorAuthInterface $twoFactorAuth,
        private readonly CsrfToken $csrf,
    ) {
    }

    /**
     * Renders the admin 2FA device-setup form.
     *
     * Anonymous-accessible (login-context): returns 200 regardless of
     * session state.
     */
    #[Alps('doSetTwoFactorAuth')]
    #[JsonSchema(schema: 'get-admin-two-factor-auth-set.json')]
    #[Link(rel: 'doSetTwoFactorAuth', href: 'page://self/admin/two-factor-auth-set', method: 'put')]
    #[Link(rel: 'goAdminLogin', href: 'page://self/admin/login')]
    public function onGet(): static
    {
        $challenge = $this->setupChallenge();
        $authKey = $challenge?->authKey ?? '';
        $form = $this->formFactory->newInstance(AdminTwoFactorAuthForm::class);
        assert($form instanceof AdminTwoFactorAuthForm);
        if ($authKey !== '') {
            $form->fillValues(['authKey' => $authKey]);
        }

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goAdminTwoFactorAuthSet',
            'fields' => ['deviceToken', 'authKey', 'csrfToken'],
            // The QR-code JS reads authKey. It is present only when a
            // password-verified setup challenge exists; otherwise the empty
            // placeholder keeps anonymous GET render fidelity.
            'authKey' => $authKey,
            'memberName' => '',
            'shopName' => 'BeMart',
            'csrfToken' => $this->csrf->token,
            'form' => $form,
        ];

        return $this;
    }

    private function setupChallenge(): AdminTwoFactorChallenge|null
    {
        $challenge = $this->loginChallenge->setupChallenge();
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

        $this->loginChallenge->startSetup(
            $admin->adminId,
            $admin->loginId,
            $this->twoFactorAuth->generateSecret(),
        );

        return $this->loginChallenge->setupChallenge();
    }

    /**
     * Registers the TOTP device after confirming the first code
     * (doSetTwoFactorAuth). ALPS marks this `idempotent` → PUT.
     *
     * The pending login identity and server-generated candidate secret are
     * read from {@see HtmlAdminLoginChallengeAdapter}. Legacy client fields
     * (`loginId`, `authKey`) may still arrive from old forms/tests, but are
     * deliberately ignored and never forwarded to the Be transition.
     *
     * Failure mapping:
     *   - Invalid CSRF                  → 403 (interceptor)
     *   - Missing pending setup         → 403
     *   - SemanticVariableException     → 400 (malformed code)
     *   - TwoFactorAuthFailedException  → 400 (first code mismatch)
     *
     * @psalm-taint-source input $deviceToken
     */
    #[Alps('doSetTwoFactorAuth')]
    #[JsonSchema(schema: 'put-admin-two-factor-auth-set.json', params: 'put-admin-two-factor-auth-set.param.json')]
    #[CsrfProtected]
    #[Link(rel: 'goTwoFactorAuth', href: 'page://self/admin/two-factor-auth')]
    #[Link(rel: 'goAdminHome', href: 'page://self/admin/index')]
    public function onPut(string $deviceToken, string|null $loginId = null, string|null $authKey = null): static
    {
        unset($loginId, $authKey);

        $challenge = $this->loginChallenge->setupChallenge();
        if ($challenge === null || $challenge->authKey === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => '二要素認証の設定チャレンジがありません。'];

            return $this;
        }

        $final = ($this->becoming)(new SetTwoFactorAuthInput(
            loginId: $challenge->loginId,
            authKey: $challenge->authKey,
            deviceToken: $deviceToken,
        ));

        assert($final instanceof TwoFactorAuthConfigured);
        $this->loginChallenge->completeSetup($challenge);

        $this->code = Code::SEE_OTHER;
        $this->headers['Location'] = '/admin/index';
        $this->body = [
            'transitionId' => 'doSetTwoFactorAuth',
            'loginId' => $final->loginId,
            'message' => '二要素認証を設定しました。',
        ];

        return $this;
    }
}
