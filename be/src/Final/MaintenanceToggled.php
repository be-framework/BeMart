<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\MaintenanceModeInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Maintenance toggled — Final, proof an admin set the maintenance-mode
 * state (doToggleMaintenance).
 *
 *   ToggleMaintenanceInput → MaintenanceToggled   (Direct, idempotent)
 *
 * AUTHZ: no admin session → UnauthorizedAdminAccessException (403). The
 * marker-file flip is delegated to {@see MaintenanceModeInterface}.
 */
final readonly class MaintenanceToggled
{
    public bool $enabled;

    public function __construct(
        #[Input] bool $enabled,
        #[Inject] AdminSession $adminSession,
        #[Inject] MaintenanceModeInterface $maintenance,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $maintenance->setEnabled($enabled);
        $this->enabled = $enabled;
    }
}
