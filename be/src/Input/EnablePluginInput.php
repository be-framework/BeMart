<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\PluginEnabled;

/**
 * Input for doEnablePlugin — admin enables an installed plugin.
 *
 *   EnablePluginInput → PluginEnabled (Final — Direct, idempotent)
 *
 * Failure ladder:
 *   - PluginNotFoundException       → 404 (no such pluginCode)
 *   - PluginNotInstalledException   → 409 (row exists but uninstalled)
 *   - UnauthorizedAdminAccessException → 403 (no admin session)
 *
 * Idempotency (ALPS `type=idempotent`): when the plugin is already
 * enabled, no second write happens — the Final reports `changed=false`.
 *
 * STUB: in the real EC-CUBE pipeline, enabling triggers container /
 * routes regeneration. The migration scope STUBS this — the storage
 * only flips the `enabled` flag.
 *
 * @link https://schema.org/ActivateAction
 */
#[Be(PluginEnabled::class)]
final readonly class EnablePluginInput
{
    /**
     * @psalm-taint-source input $pluginCode
     */
    public function __construct(
        public string $pluginCode,
    ) {
    }
}
