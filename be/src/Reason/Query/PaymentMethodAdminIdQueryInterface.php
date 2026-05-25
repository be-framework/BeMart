<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\AllocatedId;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Generates opaque admin PaymentMethod identifiers (Wave 9θ) — same
 * shape as {@see ClassNameIdQueryInterface}.
 */
interface PaymentMethodAdminIdQueryInterface
{
    #[DbQuery('paymentMethodAdmin_next_id')]
    public function next(): AllocatedId;
}
