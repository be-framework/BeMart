<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\PluginInstalled;

/**
 * Input for doInstallPlugin — admin installs a plugin.
 *
 *   InstallPluginInput → PluginInstalled (Final — Direct, unsafe)
 *
 * AUTHZ — admin firewall: the Final pulls adminId from
 * {@see \MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface};
 * `null` raises {@see \MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException}.
 *
 * INSTALL STUB: the real EC-CUBE install pipeline downloads + unzips
 * + composer requires + runs migrations + clears cache. The migration
 * scope STUBS this — the storage simply flips `installed=true` (or
 * creates a new row) for the given pluginCode. The pluginName +
 * version are taken from the admin form (in the real flow they come
 * from the plugin's manifest.json). See the PluginStorageInterface contract.
 *
 * Idempotency: ALPS marks this as `type=unsafe`, but the storage
 * contract is idempotent at the storage layer — a re-install on an
 * already-installed plugin is a no-op. The Final surfaces this as
 * `alreadyInstalled=true` in the response body.
 *
 * Mass-assignment safety: only pluginCode + pluginName + version are
 * accepted; no path here reaches enabled or installed state booleans
 * (install always lands in installed=true, enabled=false per the
 * EC-CUBE convention; the admin must call doEnablePlugin separately).
 *
 * @link https://schema.org/InstallAction
 */
#[Be(PluginInstalled::class)]
final readonly class InstallPluginInput
{
    /**
     * Wave 8: all three fields are admin-form input.
     *
     * @psalm-taint-source input $pluginCode
     * @psalm-taint-source input $pluginName
     * @psalm-taint-source input $pluginVersion
     */
    public function __construct(
        public string $pluginCode,
        public string $pluginName,
        public string $pluginVersion,
    ) {
    }
}
