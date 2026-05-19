<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\PluginStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Plugin uninstalled — Final, proof an admin uninstalled (or
 * acknowledged the already-uninstalled state of) a plugin.
 *
 *   UninstallPluginInput → PluginUninstalled (Direct, idempotent)
 *
 * Idempotency (ALPS `type=idempotent`): when the plugin is unknown or
 * already-uninstalled at the time of the request, the storage call is
 * a silent no-op and the Final reports `wasInstalled=false`. Replay
 * safety matches AdminCustomerDeleted's `alreadyDeleted` convention.
 *
 * STUB: in the real EC-CUBE pipeline, uninstall reverses migrations +
 * deletes files + clears cache. The migration scope STUBS this — the
 * storage simply drops the row.
 */
final readonly class PluginUninstalled
{
    public string $pluginCode;
    public bool $wasInstalled;

    public function __construct(
        #[Input] string $pluginCode,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] PluginStorageInterface $pluginStorage,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $before = $pluginStorage->findByCode($pluginCode);
        $wasInstalled = $before !== null && $before->installed;

        $pluginStorage->uninstall($pluginCode);

        $this->pluginCode = $pluginCode;
        $this->wasInstalled = $wasInstalled;
    }
}
