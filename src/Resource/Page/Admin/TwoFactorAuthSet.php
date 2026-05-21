<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
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
 * MISSING-BODY-FIELD follow-ups (flagged, NOT implemented — the brief
 * freezes be/): the QR-code JS needs `authKey` (the generated TOTP
 * secret), `memberName` and `shopName` to build the `otpauth://` URI.
 * Those require a Be `doSetupTwoFactorAuth` transition (secret
 * generation over the admin storage); `onPost` is not implemented here.
 * The body carries empty placeholders so the page still renders.
 */
class TwoFactorAuthSet extends ResourceObject
{
    public function __construct(
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * Renders the admin 2FA device-setup form.
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
            // MISSING-BODY-FIELD placeholders — see the class doc. The
            // QR-code JS reads these; empty until a Be 2FA transition
            // feeds them.
            'authKey' => '',
            'memberName' => '',
            'shopName' => 'BeMart',
            // Phase 3: an empty AdminTwoFactorAuthForm for the HTML port.
            'form' => $this->formFactory->newInstance(AdminTwoFactorAuthForm::class),
        ];
        assert($this->body['form'] instanceof AdminTwoFactorAuthForm);

        return $this;
    }
}
