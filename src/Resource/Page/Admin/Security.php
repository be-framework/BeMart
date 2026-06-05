<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\SecuritySettingsUpdated;
use MyVendor\BeMart\Be\Input\UpdateSecurityInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\SecurityConfigWriterInterface;
use MyVendor\BeMart\Form\AdminSecurityForm;
use Ray\WebFormModule\FormFactory;

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
    ) {
    }

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
            'admin_route_dir' => 'admin',
            'admin_allow_hosts' => $settings['admin_allow_hosts'] ?? '',
            'admin_deny_hosts' => $settings['admin_deny_hosts'] ?? '',
            'front_allow_hosts' => $settings['front_allow_hosts'] ?? '',
            'front_deny_hosts' => $settings['front_deny_hosts'] ?? '',
            'trusted_hosts' => $settings['trusted_hosts'] ?? '^localhost$',
        ]);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'isSecureRequest' => false,
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
    #[Link(rel: 'goTwoFactorAuthSet', href: 'page://self/admin/two-factor-auth-set')]
    #[CsrfProtected]
    public function onPut(
        string $adminAllowHosts = '',
        string $adminDenyHosts = '',
        string $frontAllowHosts = '',
        string $frontDenyHosts = '',
        string $trustedHosts = '',
    ): static {
        try {
            $final = ($this->becoming)(new UpdateSecurityInput(
                adminAllowHosts: $adminAllowHosts,
                adminDenyHosts: $adminDenyHosts,
                frontAllowHosts: $frontAllowHosts,
                frontDenyHosts: $frontDenyHosts,
                trustedHosts: $trustedHosts,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof SecuritySettingsUpdated);

        $this->code = Code::OK;
        $this->headers['Location'] = '/admin_setting_system_security';
        $this->body = [
            'transitionId' => 'doUpdateSecurity',
            'trustedHosts' => $final->trustedHosts,
            'message' => 'セキュリティ設定を更新しました。',
        ];

        return $this;
    }
}
