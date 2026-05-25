<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminProductCsvExported;

/**
 * Input for goExportProduct — admin downloads the product master as
 * CSV.
 *
 *   AdminExportProductInput → AdminProductCsvExported  (Direct, safe)
 *
 * Wave 8 first iteration: no filter parameters yet. Empty-constructor
 * Input — same shape as {@see AdminLogoutInput}; the chain has no body
 * fields because the export is a verb on the current admin session.
 * The filter scope (which mirrors goProductList) is deferred to Phase 2
 * — see {@see AdminProductCsvExported}.
 *
 * Note: doImportProductCsv is INTENTIONALLY NOT MIGRATED in Wave 8.
 * The EC-CUBE importer parses a CSV against the dtb_product schema
 * (insert OR update per row), validates against a multi-column
 * uniqueness contract, and orchestrates an extended PurchaseFlow-like
 * service surface. That depth doesn't fit a single-day migration
 * agent and would force the JSON-backed fake product handler to grow a bulk-upsert
 * surface that contradicts the CQRS split. Phase 2 will land it as a
 * dedicated Cascade Diamond pattern (`insurance-claim` demo).
 *
 * @link https://schema.org/DownloadAction
 */
#[Be(AdminProductCsvExported::class)]
final readonly class AdminExportProductInput
{
    public function __construct()
    {
    }
}
