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
use MyVendor\BeMart\Be\Final\TwoFactorAuthConfigured;
use MyVendor\BeMart\Be\Input\SetTwoFactorAuthInput;
use MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface;
use MyVendor\BeMart\Form\AdminTwoFactorAuthForm;
use Ray\WebFormModule\FormFactory;

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
 * EC-CUBE's controller generates a TOTP secret, renders it as a QR code
 * (the JS in the template builds the `otpauth://` URI) and verifies the
 * first token. There is no Be Framework 2FA transition (no such id in
 * `alps.json`, and the be/ domain layer is frozen for this wave), so
 * this resource is a THIN RENDERER: `onGet` exposes an
 * {@see AdminTwoFactorAuthForm} as `body['form']` for the HTML page.
 *
 * Hard ActionRedirect completion: `onGet` now seeds `authKey` with a
 * freshly generated TOTP secret (for the QR code) via the
 * {@see TwoFactorAuthInterface} boundary, and `onPut` drives the Be
 * `doSetTwoFactorAuth` transition ({@see SetTwoFactorAuthInput} →
 * {@see TwoFactorAuthConfigured}) — register the secret, then confirm by
 * verifying the first device code.
 */
class TwoFactorAuthSet extends ResourceObject
{
    public function __construct(
        private readonly FormFactory $formFactory,
        private readonly BecomingInterface $becoming,
        private readonly TwoFactorAuthInterface $twoFactorAuth,
    ) {
    }

    /**
     * Renders the admin 2FA device-setup form with a generated secret.
     *
     * Anonymous-accessible (login-context): returns 200 regardless of
     * session state.
     */
    #[Link(rel: 'goAdminLogin', href: 'page://self/admin/login')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goAdminTwoFactorAuthSet',
            'fields' => ['device_token', 'auth_key'],
            // The QR-code JS reads these to build the `otpauth://` URI.
            // authKey is a fresh secret the admin confirms via onPut.
            'authKey' => $this->twoFactorAuth->generateSecret(),
            'memberName' => '',
            'shopName' => 'BeMart',
            // Phase 3: an empty AdminTwoFactorAuthForm for the HTML port.
            'form' => $this->formFactory->newInstance(AdminTwoFactorAuthForm::class),
        ];
        assert($this->body['form'] instanceof AdminTwoFactorAuthForm);

        return $this;
    }

    /**
     * Registers the TOTP device after confirming the first code
     * (doSetTwoFactorAuth). ALPS marks this `idempotent` → PUT.
     *
     * Failure mapping:
     *   - Invalid CSRF                  → 403 (interceptor)
     *   - SemanticVariableException     → 400 (malformed code)
     *   - TwoFactorAuthFailedException  → 400 (first code mismatch)
     *
     * @psalm-taint-source input $loginId
     * @psalm-taint-source input $authKey
     * @psalm-taint-source input $deviceToken
     */
    #[CsrfProtected]
    #[Link(rel: 'goAdminHome', href: 'page://self/admin/index')]
    public function onPut(string $loginId, string $authKey, string $deviceToken): static
    {
        try {
            $final = ($this->becoming)(new SetTwoFactorAuthInput(
                loginId: $loginId,
                authKey: $authKey,
                deviceToken: $deviceToken,
            ));
        } catch (SemanticVariableException | TwoFactorAuthFailedException) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => '認証コードが正しくありません。'];

            return $this;
        }

        assert($final instanceof TwoFactorAuthConfigured);

        $this->code = Code::OK;
        $this->headers['Location'] = '/admin';
        $this->body = [
            'transitionId' => 'doSetTwoFactorAuth',
            'loginId' => $final->loginId,
            'message' => '二要素認証を設定しました。',
        ];

        return $this;
    }
}
