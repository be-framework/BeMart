<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Auth\HtmlAdminLoginChallengeAdapter;
use MyVendor\BeMart\Be\Final\TwoFactorAuthConfigured;
use MyVendor\BeMart\Be\Input\SetTwoFactorAuthInput;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface;
use MyVendor\BeMart\Form\AdminTwoFactorAuthForm;
use Ray\Csrf\Attribute\CsrfToken;
use Ray\Csrf\CsrfTokenInterface;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE 管理者2段階認証設定 — Setting/System Tier-2.
 *
 * POST-AUTH admin-self 2FA edit. Mirrors EC-CUBE's
 * `admin_setting_system_two_factor_auth_edit` (TwoFactorAuthController::edit,
 * methods GET+POST): the screen is reached only AFTER admin login and
 * operates exclusively on the logged-in admin (`$this->getUser()`). The
 * identity is NEVER a client-supplied loginId — it is always the
 * authenticated session principal.
 *
 * `onGet` generates a server-side TOTP secret bound to the authenticated
 * admin's pending setup challenge and exposes it as `authKey` so the QR JS
 * renders a real secret. `onPost` confirms the first device code and drives
 * the Be `doSetTwoFactorAuth` transition ({@see SetTwoFactorAuthInput} →
 * {@see TwoFactorAuthConfigured}) for the SESSION admin only.
 *
 * Security boundary (migration-status §4 item 8): this is a sibling of the
 * PRE-AUTH login-context {@see TwoFactorAuthSet} page, not a repoint to it.
 * The pre-auth surface is left untouched. The candidate identity + secret
 * come solely from the adminId-keyed setup challenge, and `onPost` pins
 * `$challenge->adminId === $adminSession->adminId`, so a stale pre-auth
 * challenge for another principal can never be consumed here. Client
 * `loginId`/`authKey` are accepted for transport compatibility but unset at
 * entry and never forwarded to the transition.
 */
class TwoFactorAuthEdit extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
        private readonly BecomingInterface $becoming,
        private readonly HtmlAdminLoginChallengeAdapter $loginChallenge,
        private readonly AdminQueryInterface $adminQuery,
        private readonly TwoFactorAuthInterface $twoFactorAuth,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /** ALPS `goTwoFactorAuthEdit` に対応する GET 操作。 */
    #[Alps('goTwoFactorAuthEdit')]
    #[JsonSchema(schema: 'get-admin-two-factor-auth-edit.json')]
    #[Link(rel: 'doSetTwoFactorAuth', href: 'page://self/admin/two-factor-auth-edit', method: 'post')]
    public function onGet(): static
    {
        $adminId = $this->adminSession->adminId;
        if ($adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $authKey = $this->startSetupChallenge($adminId);

        $form = $this->formFactory->newInstance(AdminTwoFactorAuthForm::class);
        assert($form instanceof AdminTwoFactorAuthForm);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            // Server-generated secret bound to the authenticated admin's
            // setup challenge; the QR JS reads it. Empty only if the admin
            // record cannot be resolved.
            'authKey' => $authKey,
            'memberName' => $adminId,
            'shopName' => 'BeMart',
        ];

        return $this;
    }

    /**
     * Confirms the first TOTP code and registers the device for the
     * SESSION admin (doSetTwoFactorAuth). Self-post (action=""), so the
     * audit binds it to `onPost`; ALPS marks the op `idempotent`.
     *
     * Trusted identity is ONLY `adminSession->adminId`; trusted candidate
     * secret is ONLY the server value in the adminId-keyed setup challenge.
     * Client `loginId`/`authKey` are ignored. The guard requires the live
     * setup challenge to belong to the session admin, so a leftover pre-auth
     * challenge for a different principal can never be consumed here.
     *
     * @psalm-taint-source input $deviceToken
     */
    #[Alps('doSetTwoFactorAuth')]
    #[JsonSchema(schema: 'put-admin-two-factor-auth-set.json', params: 'put-admin-two-factor-auth-set.param.json')]
    #[CsrfToken]
    #[Link(rel: 'goTwoFactorAuth', href: 'page://self/admin/two-factor-auth')]
    #[Link(rel: 'goAdminHome', href: 'page://self/admin/index')]
    public function onPost(string $deviceToken, string|null $loginId = null, string|null $authKey = null): static
    {
        unset($loginId, $authKey);

        $adminId = $this->adminSession->adminId;
        if ($adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $challenge = $this->loginChallenge->setupChallenge();
        if ($challenge === null || $challenge->authKey === null || $challenge->adminId !== $adminId) {
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

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'doSetTwoFactorAuth',
            'loginId' => $final->loginId,
            'message' => '二要素認証を設定しました。',
        ];

        return $this;
    }

    /**
     * Starts (or reuses) a server-side setup challenge for the
     * AUTHENTICATED admin and returns its candidate secret. Identity is the
     * session adminId resolved through {@see AdminQueryInterface::item}, never
     * a client value.
     */
    private function startSetupChallenge(string $adminId): string
    {
        $existing = $this->loginChallenge->setupChallenge();
        if ($existing !== null && $existing->adminId === $adminId && $existing->authKey !== null) {
            return $existing->authKey;
        }

        $admin = $this->adminQuery->item($adminId);
        if ($admin === null) {
            return '';
        }

        $secret = $this->twoFactorAuth->generateSecret();
        $this->loginChallenge->startSetup($admin->adminId, $admin->loginId, $secret);

        return $secret;
    }
}
