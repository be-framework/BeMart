<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CategoryCsvImported;

/**
 * Input for doImportCategoryCsv — admin bulk-uploads categories
 * (Wave 7, **Phase 2 stub**).
 *
 *   ImportCategoryCsvInput → CategoryCsvImported (Direct, admin AUTHZ)
 *
 * The HTTP-form file upload + multipart handling is out of scope for
 * this slice; this Input accepts the CSV body as a plain string so
 * the AUTHZ contract is testable in isolation. Phase 2 will add real
 * line-by-line parsing, parent_id resolution, and dry-run preview —
 * the present iteration just rejects empty payloads, records the
 * line count (header excluded), and surfaces a "not implemented"
 * notice so callers know the persisted state is unchanged.
 */
#[Be(CategoryCsvImported::class)]
final readonly class ImportCategoryCsvInput
{
    /**
     * @psalm-taint-source input $csv
     */
    public function __construct(
        public string $csv,
    ) {
    }
}
