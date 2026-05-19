<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\PluginStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Plugin installed — Final, proof an admin installed (or re-installed)
 * a plugin.
 *
 *   InstallPluginInput → PluginInstalled (Direct, unsafe)
 *
 * AUTHZ — admin firewall: AdminSession::adminId() === null →
 * UnauthorizedAdminAccessException (403).
 *
 * INSTALL STUB: see {@see PluginStorageInterface::install}. The real
 * EC-CUBE pipeline (download / unzip / migrate / cache) is STUBBED at
 * the storage layer for the migration scope; this Final just calls
 * into the storage.
 *
 * Idempotency surface: when the plugin was already installed at the
 * time of the request, the Final reports `alreadyInstalled=true` and
 * the storage write is a no-op. The new-vs-existing distinction
 * matters because the admin UI shows different success copy ("installed
 * Foo 1.0.0" vs "Foo 1.0.0 was already installed").
 *
 * Mass-assignment safety: only pluginCode + pluginName + pluginVersion
 * are accepted; the `installed` / `enabled` flags are set by the
 * storage per EC-CUBE convention (install → installed=true,
 * enabled=false).
 */
final readonly class PluginInstalled
{
    public string $pluginCode;
    public string $pluginName;
    public string $pluginVersion;
    public bool $installed;
    public bool $enabled;
    public bool $alreadyInstalled;

    public function __construct(
        #[Input] string $pluginCode,
        #[Input] string $pluginName,
        #[Input] string $pluginVersion,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] PluginStorageInterface $pluginStorage,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $existing = $pluginStorage->findByCode($pluginCode);
        $alreadyInstalled = $existing !== null && $existing->installed;

        $pluginStorage->install($pluginCode, $pluginName, $pluginVersion);

        $after = $pluginStorage->findByCode($pluginCode);
        // After install the row MUST exist; the static analyzer needs
        // the explicit guard.
        if ($after === null) {
            // Defensive — should never happen given the storage
            // contract; surfaces a Bug rather than silently passing.
            throw new \LogicException('Plugin install storage post-condition violated.');
        }

        $this->pluginCode = $after->pluginCode;
        $this->pluginName = $after->pluginName;
        $this->pluginVersion = $after->version;
        $this->installed = $after->installed;
        $this->enabled = $after->enabled;
        $this->alreadyInstalled = $alreadyInstalled;
    }
}
