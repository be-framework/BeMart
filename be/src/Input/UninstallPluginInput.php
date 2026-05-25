<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\PluginUninstalled;

/**
 * Input for doUninstallPlugin — admin uninstalls a plugin.
 *
 *   UninstallPluginInput → PluginUninstalled (Final — Direct, idempotent)
 *
 * Idempotency (ALPS `type=idempotent`): the storage silently no-ops
 * when the plugin is unknown or already-uninstalled. The Final
 * surfaces `wasInstalled` so the response distinguishes the no-op
 * branch from a real teardown.
 *
 * AUTHZ in the Final (AdminSession). Mass-assignment safety:
 * only pluginCode is accepted.
 *
 * @link https://schema.org/DeleteAction
 */
#[Be(PluginUninstalled::class)]
final readonly class UninstallPluginInput
{
    /**
     * Wave 8: admin-form input (selected from the plugin grid row).
     *
     * @psalm-taint-source input $pluginCode
     */
    public function __construct(
        public string $pluginCode,
    ) {
    }
}
