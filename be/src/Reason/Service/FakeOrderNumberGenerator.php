<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Override;

use function bin2hex;
use function random_bytes;

/**
 * Generates a 32-char hex order number (16 random bytes), matching the
 * convention of FakeCustomerIdGenerator. Production binding will call
 * EC-CUBE's OrderRepository::getOrderId() instead.
 */
final class FakeOrderNumberGenerator implements OrderNumberGeneratorInterface
{
    #[Override]
    public function generate(): string
    {
        return bin2hex(random_bytes(16));
    }
}
