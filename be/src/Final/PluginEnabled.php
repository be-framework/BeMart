<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\PluginNotFoundException;
use MyVendor\BeMart\Be\Exception\PluginNotInstalledException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\PluginStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Plugin enabled — Final, proof an admin enabled an installed plugin.
 *
 *   EnablePluginInput → PluginEnabled (Direct, idempotent)
 *
 * Failure ladder:
 *   1. No admin session     → UnauthorizedAdminAccessException  (403)
 *   2. Unknown pluginCode   → PluginNotFoundException            (404)
 *   3. Row uninstalled      → PluginNotInstalledException        (409)
 *
 * Idempotency: when the plugin is already enabled, the storage's
 * setEnabled is a no-op and the Final reports `changed=false`.
 *
 * STUB: container/routes regeneration is out of scope for the
 * migration; the storage only flips the `enabled` flag.
 */
final readonly class PluginEnabled
{
    public string $pluginCode;
    public bool $enabled;
    public bool $changed;

    public function __construct(
        #[Input] string $pluginCode,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] PluginStorageInterface $pluginStorage,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $existing = $pluginStorage->item($pluginCode);
        if ($existing === null) {
            throw new PluginNotFoundException();
        }

        if (! $existing->installed) {
            throw new PluginNotInstalledException();
        }

        $wasEnabled = $existing->enabled;

        // Storage is idempotent on the enabled value — call
        // unconditionally and surface `changed` based on the prior state.
        $pluginStorage->setEnabled($pluginCode, true);

        $this->pluginCode = $pluginCode;
        $this->enabled = true;
        $this->changed = ! $wasEnabled;
    }
}
