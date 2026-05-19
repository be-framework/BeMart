<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use DomainException;

use function mb_strlen;

/**
 * Admin identifier — non-empty opaque string. The fixture uses
 * 32-char hex with `ad` prefix, but the Semantic only enforces
 * non-empty + a generous length cap so the validator does not need
 * to know about the fixture's prefix convention.
 *
 * Wave 8 introduces this Semantic for completeness — the adminId
 * surfaces on Being / Final constructors via `#[Input] string $adminId`
 * and Be Semantic dispatch would otherwise emit a "not registered"
 * notice during tests.
 */
final class AdminId
{
    #[Validate]
    public function validate(string $adminId): void
    {
        $length = mb_strlen($adminId);
        if ($length < 1 || $length > 64) {
            throw new class extends DomainException {
                public function __construct()
                {
                    parent::__construct('Invalid admin id format.');
                }
            };
        }
    }
}
