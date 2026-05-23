<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\CustomerInitialPointInterface;
use Override;

/**
 * Pilot 4 reads a hard-coded 100 pt as the default registration bonus.
 * Phase 2 will replace this with `BaseInfo::getWelcomePoint()` (or
 * equivalent shop config).
 */
final class FakeCustomerInitialPoint implements CustomerInitialPointInterface
{
    #[Override]
    public function initial(): int
    {
        return 100;
    }
}
