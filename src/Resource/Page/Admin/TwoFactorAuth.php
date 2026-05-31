<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Exception\TwoFactorAuthFailedException;
use MyVendor\BeMart\Be\Final\TwoFactorAuthVerified;
use MyVendor\BeMart\Be\Input\VerifyTwoFactorAuthInput;
use MyVendor\BeMart\Form\AdminTwoFactorAuthForm;
use Ray\WebFormModule\FormFactory;

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
 * member's stored secret. There is no Be Framework 2FA transition (no
 * such id in `alps.json`, and the be/ domain layer is frozen for this
 * wave), so this resource is a THIN RENDERER: `onGet` exposes an
 * {@see AdminTwoFactorAuthForm} as `body['form']` for the HTML page.
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
    ) {
    }

    /**
     * Renders the admin 2FA challenge form.
     *
     * Anonymous-accessible (login-context): returns 200 regardless of
     * session state — the admin firewall guard is downstream of a
     * successful challenge.
     */
    #[Link(rel: 'goAdminLogin', href: 'page://self/admin/login')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goAdminTwoFactorAuth',
            'fields' => ['device_token'],
            // Phase 3: an empty AdminTwoFactorAuthForm for the HTML port.
            'form' => $this->formFactory->newInstance(AdminTwoFactorAuthForm::class),
        ];
        assert($this->body['form'] instanceof AdminTwoFactorAuthForm);

        return $this;
    }

    /**
     * Verifies the submitted TOTP code (doVerifyTwoFactorAuth).
     *
     * Login-context: no admin-firewall guard (the session is elevated by
     * the adapter only on success). The candidate `loginId` is
     * round-tripped from the pre-auth step.
     *
     * Failure mapping:
     *   - Invalid CSRF                  → 403 (interceptor)
     *   - SemanticVariableException     → 400 (malformed code)
     *   - TwoFactorAuthFailedException  → 400 (code mismatch)
     *
     * @psalm-taint-source input $loginId
     * @psalm-taint-source input $deviceToken
     */
    #[CsrfProtected]
    #[Link(rel: 'goAdminHome', href: 'page://self/admin/index')]
    public function onPost(string $loginId, string $deviceToken): static
    {
        try {
            $final = ($this->becoming)(new VerifyTwoFactorAuthInput(
                loginId: $loginId,
                deviceToken: $deviceToken,
            ));
        } catch (SemanticVariableException | TwoFactorAuthFailedException) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => '認証コードが正しくありません。'];

            return $this;
        }

        assert($final instanceof TwoFactorAuthVerified);

        $this->code = Code::OK;
        $this->headers['Location'] = '/admin';
        $this->body = [
            'transitionId' => 'doVerifyTwoFactorAuth',
            'loginId' => $final->loginId,
            'message' => '二要素認証を確認しました。',
        ];

        return $this;
    }
}
