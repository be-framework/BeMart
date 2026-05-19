<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Override;

use function bin2hex;
use function random_bytes;

/**
 * Generates a 32-char hex id — same shape as
 * {@see FakeAddressIdGenerator}. EC-CUBE 4.3 uses sequential bigint ids
 * for dtb_category; the Be / BEAR side opts for opaque strings so tests
 * do not depend on auto-increment state.
 */
final class FakeCategoryIdGenerator implements CategoryIdGeneratorInterface
{
    #[Override]
    public function generate(): string
    {
        return bin2hex(random_bytes(16));
    }
}
