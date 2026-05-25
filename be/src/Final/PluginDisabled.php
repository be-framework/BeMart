<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\PluginNotFoundException;
use MyVendor\BeMart\Be\Exception\PluginNotInstalledException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\PluginStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Plugin disabled — Final, proof an admin disabled an installed
 * plugin.
 *
 *   DisablePluginInput → PluginDisabled (Direct, idempotent)
 *
 * Mirrors {@see PluginEnabled} with the opposite flag. Same failure
 * ladder (403 / 404 / 409) and same STUB caveat (no container regen).
 */
final readonly class PluginDisabled
{
    public string $pluginCode;
    public bool $enabled;
    public bool $changed;

    public function __construct(
        #[Input] string $pluginCode,
        #[Inject] AdminSession $adminSession,
        #[Inject] PluginStorageInterface $pluginStorage,
    ) {
        if ($adminSession->adminId === null) {
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

        $pluginStorage->setEnabled($pluginCode, false);

        $this->pluginCode = $pluginCode;
        $this->enabled = false;
        $this->changed = $wasEnabled;
    }
}
