<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminOrderCsvExported;

/**
 * Input for goExportOrder — admin downloads the order list as CSV
 * (Wave 9η, light implementation).
 *
 *   AdminExportOrderInput → AdminOrderCsvExported  (Direct, safe read)
 *
 * ALPS doc verbatim: "受注データをCSV形式でダウンロードする。検索条件で
 * 絞り込み可能。" The Wave 9η first iteration emits the full corpus —
 * the search-condition filter is deferred to Phase 2 (mirrors the
 * Wave 8 product CSV export decision).
 */
#[Be(AdminOrderCsvExported::class)]
final readonly class AdminExportOrderInput
{
    public function __construct()
    {
    }
}
