<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\SecuritySettingsUpdated;

/**
 * Input for `doUpdateSecurity` — an admin edits the host allow/deny lists
 * and the trusted-hosts pattern (Hard ActionRedirect completion).
 *
 *   UpdateSecurityInput → SecuritySettingsUpdated   (Direct, idempotent, admin AUTHZ)
 *
 * Derived from EC-CUBE's `admin_setting_system_security` route
 * (`SecurityController` + `SecurityType`). ALPS marks it `idempotent` —
 * the settings are set to explicit values, so re-submitting the same form
 * is a no-op. The config-file write + firewall reload side-effect is
 * isolated in {@see \MyVendor\BeMart\Be\Reason\Service\SecurityConfigWriterInterface}.
 */
#[Be(SecuritySettingsUpdated::class)]
final readonly class UpdateSecurityInput
{
    /**
     * @psalm-taint-source input $adminAllowHosts
     * @psalm-taint-source input $adminDenyHosts
     * @psalm-taint-source input $frontAllowHosts
     * @psalm-taint-source input $frontDenyHosts
     * @psalm-taint-source input $trustedHosts
     */
    public function __construct(
        public string $adminAllowHosts = '',
        public string $adminDenyHosts = '',
        public string $frontAllowHosts = '',
        public string $frontDenyHosts = '',
        public string $trustedHosts = '',
    ) {
    }
}
