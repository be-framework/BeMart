<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/** Issues the customer-facing number of a new order */
interface OrderNoIssuerInterface
{
    /** @return non-empty-string */
    public function issue(): string;
}
