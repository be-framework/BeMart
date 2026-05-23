<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Query\Result\GeneratedId;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Generates opaque admin PaymentMethod identifiers (Wave 9θ) — same
 * shape as {@see ClassNameIdGeneratorInterface}.
 */
interface PaymentMethodAdminIdGeneratorInterface
{
    #[DbQuery('paymentMethodAdmin_next_id')]
    public function generate(): GeneratedId;
}
