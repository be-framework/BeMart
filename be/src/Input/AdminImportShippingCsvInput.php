<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminShippingCsvImported;

/**
 * Input for doImportShippingCsv — admin bulk-uploads tracking numbers
 * against existing shipping rows (Wave 9η).
 *
 *   AdminImportShippingCsvInput → AdminShippingCsvImported
 *                                  (Direct, admin AUTHZ)
 *
 * The CSV parser updates known order rows and skips unknown rows without
 * failing the whole import, matching EC-CUBE's per-row tolerance.
 */
#[Be(AdminShippingCsvImported::class)]
final readonly class AdminImportShippingCsvInput
{
    /**
     * @psalm-taint-source input $csv
     */
    public function __construct(
        public string $csv,
    ) {
    }
}
