<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminCustomerCsvExported;

/**
 * Input for goExportCustomer — admin downloads the customer master as
 * CSV (Wave 9).
 *
 *   AdminExportCustomerInput → AdminCustomerCsvExported  (Direct, safe)
 *
 * Mirrors Wave 8β {@see ExportCategoryInput} and Wave 8α
 * {@see AdminExportProductInput}: empty-constructor Input — the export
 * is a verb on the current admin session. ALPS doc allows a
 * search-condition filter but the Wave 9 first iteration emits the full
 * corpus to keep parity with the other Wave 8 export endpoints; the
 * filter scope is Phase 2.
 *
 * Note: there is no doImportCustomerCsv counterpart on the customer
 * side — customer rows arrive via doCreateCustomer (admin) or
 * doRegisterCustomer (self-serve). Only the export half exists.
 *
 * @link https://schema.org/DownloadAction
 */
#[Be(AdminCustomerCsvExported::class)]
final readonly class AdminExportCustomerInput
{
    public function __construct()
    {
    }
}
