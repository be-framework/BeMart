<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CategoryCsvExported;

/**
 * Input for goExportCategory — admin downloads the category list as
 * CSV (Wave 7).
 *
 *   ExportCategoryInput → CategoryCsvExported (Direct, admin AUTHZ,
 *                                              safe read)
 *
 * Light implementation: aggregate every category row into RFC 4180
 * CSV text. The Resource layer renders the body as text/csv. This is
 * NOT a Phase 2 stub — the round trip is real, just minimal.
 */
#[Be(CategoryCsvExported::class)]
final readonly class ExportCategoryInput
{
    public function __construct()
    {
    }
}
