<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\MaintenanceToggled;

/**
 * Input for `doToggleMaintenance` — an admin switches maintenance mode on
 * or off (Hard ActionRedirect completion). ALPS marks it `idempotent` —
 * `enabled` is an explicit target state, not a blind flip. The
 * marker-file side-effect is isolated in
 * {@see \MyVendor\BeMart\Be\Reason\Service\MaintenanceModeInterface}.
 */
#[Be(MaintenanceToggled::class)]
final readonly class ToggleMaintenanceInput
{
    /** @psalm-taint-source input $enabled */
    public function __construct(
        public bool $enabled,
    ) {
    }
}
