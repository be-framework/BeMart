<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

interface CustomerInitialPointInterface
{
    /** Points granted to a brand-new customer at registration. */
    public function initial(): int;
}
