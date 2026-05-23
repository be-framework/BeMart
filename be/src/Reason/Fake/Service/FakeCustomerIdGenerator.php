<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\CustomerIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Query\Result\AllocatedId;
use Override;
use function bin2hex;
use function random_bytes;

/**
 * Generates a 32-char hex id (16 random bytes). EC-CUBE 4.3 uses
 * sequential bigint ids; the Be / BEAR side opts for opaque strings
 * so tests do not depend on auto-increment state.
 */
final class FakeCustomerIdGenerator implements CustomerIdGeneratorInterface
{
    #[Override]
    public function generate(): AllocatedId
    {
        return new AllocatedId(bin2hex(random_bytes(16)));
    }
}
