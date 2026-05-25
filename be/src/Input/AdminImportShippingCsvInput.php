<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminShippingCsvImported;

/**
 * Input for doImportShippingCsv — admin bulk-uploads tracking number /
 * ship-date updates against existing shipping rows (Wave 9η,
 * **Phase 2 stub**).
 *
 *   AdminImportShippingCsvInput → AdminShippingCsvImported
 *                                  (Direct, admin AUTHZ)
 *
 * Mirrors the Wave 8 {@see ImportCategoryCsvInput} stub: accepts the
 * CSV body as a plain string, surfaces `accepted=false` with a notice
 * so the AUTHZ contract is exercised without pretending the parser
 * exists. Real line-by-line ingestion (tracking-number column,
 * shipDate parsing, dry-run preview) is Phase 2.
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
