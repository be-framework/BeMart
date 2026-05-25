<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\CsvTypeFormatException;

/**
 * CSV export type discriminator — Wave 9 (doUpdateCsv).
 *
 * EC-CUBE 4.3 dtb_csv carries a `csv_type` column that addresses one of
 * four built-in CSV export categories:
 *
 *   1 = order      (受注)
 *   2 = customer   (会員)
 *   3 = product    (商品)
 *   4 = shipping   (出荷)
 *
 * Anything outside that set is rejected — the storage cannot tell
 * which export the columns belong to otherwise.
 */
final class CsvType
{
    #[Validate]
    public function validate(int $csvType): void
    {
        if ($csvType < 1 || $csvType > 4) {
            throw new CsvTypeFormatException();
        }
    }
}
