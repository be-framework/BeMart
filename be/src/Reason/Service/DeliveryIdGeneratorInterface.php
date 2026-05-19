<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Generates opaque Delivery identifiers (Wave 9θ).
 */
interface DeliveryIdGeneratorInterface
{
    public function generate(): string;
}
