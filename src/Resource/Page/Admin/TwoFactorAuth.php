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
 * DOMAIN follow-up (flagged, NOT implemented — the brief freezes be/):
 * the token verification needs a Be `doVerifyTwoFactorAuth` transition.
 * `onPost` is intentionally NOT implemented here.
 */
class TwoFactorAuth extends ResourceObject
{
    public function __construct(
        private readonly FormFactory $formFactory,
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
}
