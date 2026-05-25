<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use DomainException;

/**
 * Admin work / active flag — ALPS descriptor `work` (Wave 8). EC-CUBE
 * mtb_work: 0=NON_ACTIVE / 1=ACTIVE. Closed set; any other value is
 * malformed.
 *
 * Introduced for completeness — Member transitions pass `work`
 * around as `#[Input] int $work` and Be Semantic dispatch would
 * otherwise emit a "not registered" notice during tests.
 */
final class Work
{
    #[Validate]
    public function validate(int $work): void
    {
        if ($work !== 0 && $work !== 1) {
            throw new class extends DomainException {
                public function __construct()
                {
                    parent::__construct('Invalid work flag (must be 0 or 1).');
                }
            };
        }
    }
}
