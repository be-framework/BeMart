<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\NameKeywordFormatException;

use function mb_strlen;

/**
 * Admin customer-list name keyword — Wave 5 (goCustomerList).
 *
 * Substring filter applied across name01 / name02 / companyName in
 * customer search. Optional (null disables the filter).
 * Bounded length to keep the search scan trivial and to avoid stuffing
 * giant strings through the admin form.
 */
final class NameKeyword
{
    #[Validate]
    public function validate(string|null $nameKeyword): void
    {
        if ($nameKeyword === null || $nameKeyword === '') {
            return;
        }

        if (mb_strlen($nameKeyword) > 100) {
            throw new NameKeywordFormatException();
        }
    }
}
