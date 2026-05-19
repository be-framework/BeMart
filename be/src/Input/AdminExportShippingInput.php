<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminShippingCsvExported;

/**
 * Input for goExportShipping — admin downloads the shipping list as
 * CSV (Wave 9η).
 *
 *   AdminExportShippingInput → AdminShippingCsvExported (Direct, safe read)
 *
 * ALPS doc verbatim: "配送情報をCSV形式でダウンロードする。出荷管理用、
 * 伝票番号入力後のインポートと対をなす。" Pairs with doImportShippingCsv
 * — the admin's workflow is "download → fill tracking numbers offline →
 * upload back". Wave 9η provides a minimal export header + one row per
 * recorded shipping address; the tracking-number column is exposed
 * empty (Phase 2 will materialise it once {@see \MyVendor\BeMart\Be\Reason\Entity\ShippingAddressEntity}
 * grows the field).
 */
#[Be(AdminShippingCsvExported::class)]
final readonly class AdminExportShippingInput
{
    public function __construct()
    {
    }
}
