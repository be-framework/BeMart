<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\CompanyNameFormatException;

use function mb_strlen;

/**
 * Company name — EC-CUBE 4.3 dtb_customer.company_name. Optional;
 * used for B2B / invoice contexts.
 */
final class CompanyName
{
    #[Validate]
    public function validate(string|null $companyName): void
    {
        if ($companyName === null || $companyName === '') {
            return;
        }

        if (mb_strlen($companyName) > 100) {
            throw new CompanyNameFormatException();
        }
    }
}
