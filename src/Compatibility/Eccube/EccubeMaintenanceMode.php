<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Reason\Service\MaintenanceModeInterface;
use Override;

/**
 * EC-CUBE-compatible maintenance-mode boundary.
 *
 * Holds the maintenance flag in process (bound as a singleton) so
 * `doToggleMaintenance` is exercisable end to end. Flipping the real
 * marker file the kernel checks is the production cutover residual
 * (migration-status §4).
 */
final class EccubeMaintenanceMode implements MaintenanceModeInterface
{
    private bool $enabled = false;

    #[Override]
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    #[Override]
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}
