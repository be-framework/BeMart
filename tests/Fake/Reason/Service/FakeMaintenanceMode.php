<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\MaintenanceModeInterface;
use Override;

/** Recording fake for the maintenance-mode boundary. */
final class FakeMaintenanceMode implements MaintenanceModeInterface
{
    public bool $enabled = false;

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
