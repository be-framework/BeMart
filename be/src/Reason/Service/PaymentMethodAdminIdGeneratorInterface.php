<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Generates opaque admin PaymentMethod identifiers (Wave 9θ) — same
 * shape as {@see ClassNameIdGeneratorInterface}.
 */
interface PaymentMethodAdminIdGeneratorInterface
{
    public function generate(): string;
}
