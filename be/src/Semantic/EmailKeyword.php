<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\EmailKeywordFormatException;

use function mb_strlen;

/**
 * Admin customer-list email keyword — Wave 5 (goCustomerList).
 *
 * Substring filter applied to the customer email column in
 * customer search. Optional (null disables the filter).
 * Length-bounded similar to the canonical Email semantic, though we
 * intentionally do NOT enforce the `local@domain` shape here — admins
 * commonly filter by a domain fragment ("example.com") or a name
 * fragment ("alice"), neither of which would pass a full-email check.
 */
final class EmailKeyword
{
    #[Validate]
    public function validate(string|null $emailKeyword): void
    {
        if ($emailKeyword === null || $emailKeyword === '') {
            return;
        }

        if (mb_strlen($emailKeyword) > 255) {
            throw new EmailKeywordFormatException();
        }
    }
}
