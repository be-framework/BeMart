<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Issues a customer-facing order number (dtb_order.order_no).
 *
 * EC-CUBE delegates this to OrderRepository::getOrderId() which by default
 * returns a stringified primary key, but the format is plugin-customizable.
 * The Pilot 5 fake produces a 32-character hex token — matching Pilot 4's
 * CustomerIdGenerator convention — so tests can pattern-match on shape.
 */
interface OrderNumberGeneratorInterface
{
    public function generate(): string;
}
