<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Maintenance-mode boundary (`doToggleMaintenance`).
 *
 * EC-CUBE's `MaintenanceController` flips a marker file that the kernel
 * checks to serve the maintenance page. That operational-state side-effect
 * stays behind this boundary; {@see \MyVendor\BeMart\Be\Final\MaintenanceToggled}
 * depends only on this interface.
 */
interface MaintenanceModeInterface
{
    public function isEnabled(): bool;

    public function setEnabled(bool $enabled): void;
}
