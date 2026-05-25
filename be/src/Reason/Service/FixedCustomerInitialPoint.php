<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Override;

final class FixedCustomerInitialPoint implements CustomerInitialPointInterface
{
    #[Override]
    public function initial(): int
    {
        return 0;
    }
}
