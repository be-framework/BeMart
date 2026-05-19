<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\PluginDisabled;

/**
 * Input for doDisablePlugin — admin disables an installed plugin.
 *
 *   DisablePluginInput → PluginDisabled (Final — Direct, idempotent)
 *
 * Mirrors {@see EnablePluginInput} with the opposite flag. Same
 * failure ladder (404 / 409 / 403). Same STUB caveat (no container
 * regen).
 *
 * @link https://schema.org/DeactivateAction
 */
#[Be(PluginDisabled::class)]
final readonly class DisablePluginInput
{
    /**
     * @psalm-taint-source input $pluginCode
     */
    public function __construct(
        public string $pluginCode,
    ) {
    }
}
