<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use Ray\Csrf\Attribute\CsrfToken;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\SecuritySettingsUpdated;
use MyVendor\BeMart\Be\Input\UpdateSecurityInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Csrf\CsrfTokenInterface;
use MyVendor\BeMart\Be\Reason\Service\SecurityConfigWriterInterface;
use MyVendor\BeMart\Form\AdminSecurityForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE セキュリティ管理 — Setting/System Tier-2.
 *
 * Hard ActionRedirect completion: `onGet` renders the current settings
 * read through the {@see SecurityConfigWriterInterface} boundary, and
 * `onPut` drives the Be `doUpdateSecurity` transition
 * ({@see UpdateSecurityInput} → {@see SecuritySettingsUpdated}) — the host
 * allow/deny lists and trusted-hosts pattern are written behind that
 * boundary (config/firewall side-effect isolated).
 */
class Security extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
        private readonly BecomingInterface $becoming,
        private readonly SecurityConfigWriterInterface $securityConfig,
        private readonly CsrfTokenInterface $csrf,
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /** ALPS `doUpdateSecurity` に対応する GET 操作。 */
    #[Alps('doUpdateSecurity')]
    #[JsonSchema(schema: 'get-admin-security.json')]
    #[Link(rel: 'doUpdateSecurity', href: 'page://self/admin/security', method: 'put')]
    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $settings = $this->securityConfig->read();
        $form = $this->formFactory->newInstance(AdminSecurityForm::class);
        assert($form instanceof AdminSecurityForm);
        $form->fillValues([
            'adminRouteDir' => 'admin',
            'adminAllowHosts' => $settings['admin_allow_hosts'] ?? '',
            'adminDenyHosts' => $settings['admin_deny_hosts'] ?? '',
            'frontAllowHosts' => $settings['front_allow_hosts'] ?? '',
            'frontDenyHosts' => $settings['front_deny_hosts'] ?? '',
            'trustedHosts' => $settings['trusted_hosts'] ?? '^localhost$',
        ]);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'isSecureRequest' => false,
            'csrfToken' => $this->csrf->issue(),
        ];

        return $this;
    }

    /**
     * Updates the security settings (doUpdateSecurity). ALPS marks this
     * `idempotent` → PUT.
     *
     * Failure mapping:
     *   - Invalid CSRF                     → 403 (interceptor)
     *   - SemanticVariableException        → 400
     *   - UnauthorizedAdminAccessException → 403 (no admin session)
     *
     * @psalm-taint-source input $adminAllowHosts
     * @psalm-taint-source input $adminDenyHosts
     * @psalm-taint-source input $frontAllowHosts
     * @psalm-taint-source input $frontDenyHosts
     * @psalm-taint-source input $trustedHosts
     */
    #[Alps('doUpdateSecurity')]
    #[JsonSchema(schema: 'put-admin-security.json', params: 'put-admin-security.param.json')]
    #[Link(rel: 'goTwoFactorAuthSet', href: 'page://self/admin/two-factor-auth-set')]
    #[CsrfToken]
    public function onPut(
        string $adminAllowHosts = '',
        string $adminDenyHosts = '',
        string $frontAllowHosts = '',
        string $frontDenyHosts = '',
        string $trustedHosts = '',
    ): static {
        $final = ($this->becoming)(new UpdateSecurityInput(
            adminAllowHosts: $adminAllowHosts,
            adminDenyHosts: $adminDenyHosts,
            frontAllowHosts: $frontAllowHosts,
            frontDenyHosts: $frontDenyHosts,
            trustedHosts: $trustedHosts,
        ));

        assert($final instanceof SecuritySettingsUpdated);

        ($this->mutationResponse)($this, Code::OK, '/admin/security');
        $this->body = [
            'transitionId' => 'doUpdateSecurity',
            'trustedHosts' => $final->trustedHosts,
            'message' => 'セキュリティ設定を更新しました。',
        ];

        return $this;
    }
}
