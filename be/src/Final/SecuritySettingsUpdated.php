<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\SecurityConfigWriterInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Security settings updated — Final, proof the host allow/deny lists and
 * trusted-hosts pattern were written (doUpdateSecurity).
 *
 *   UpdateSecurityInput → SecuritySettingsUpdated   (Direct, idempotent)
 *
 * AUTHZ: no admin session → UnauthorizedAdminAccessException (403). The
 * config write is delegated to
 * {@see SecurityConfigWriterInterface} (the runtime/file side-effect
 * boundary). Idempotent: the settings are written as explicit values.
 */
final readonly class SecuritySettingsUpdated
{
    public string $adminAllowHosts;
    public string $adminDenyHosts;
    public string $frontAllowHosts;
    public string $frontDenyHosts;
    public string $trustedHosts;

    public function __construct(
        #[Input] string $adminAllowHosts,
        #[Input] string $adminDenyHosts,
        #[Input] string $frontAllowHosts,
        #[Input] string $frontDenyHosts,
        #[Input] string $trustedHosts,
        #[Inject] AdminSession $adminSession,
        #[Inject] SecurityConfigWriterInterface $securityConfig,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $securityConfig->write([
            'admin_allow_hosts' => $adminAllowHosts,
            'admin_deny_hosts' => $adminDenyHosts,
            'front_allow_hosts' => $frontAllowHosts,
            'front_deny_hosts' => $frontDenyHosts,
            'trusted_hosts' => $trustedHosts,
        ]);

        $this->adminAllowHosts = $adminAllowHosts;
        $this->adminDenyHosts = $adminDenyHosts;
        $this->frontAllowHosts = $frontAllowHosts;
        $this->frontDenyHosts = $frontDenyHosts;
        $this->trustedHosts = $trustedHosts;
    }
}
