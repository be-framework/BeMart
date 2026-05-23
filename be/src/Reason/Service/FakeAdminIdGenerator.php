<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Query\Result\GeneratedId;
use Override;

use function bin2hex;
use function random_bytes;
use function substr;

/**
 * Generates a 32-char hex id prefixed with `ad` to keep the admin id
 * space visually distinct from customer ids (which generate as
 * arbitrary hex). Mirrors the seed fixture's naming convention
 * (`ad000000000000000000000000000001`).
 */
final class FakeAdminIdGenerator implements AdminIdGeneratorInterface
{
    #[Override]
    public function generate(): GeneratedId
    {
        // 30 hex chars of randomness + 2-char "ad" prefix = 32 chars.
        return new GeneratedId('ad' . substr(bin2hex(random_bytes(16)), 0, 30));
    }
}
