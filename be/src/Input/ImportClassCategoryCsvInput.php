<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ClassCategoryCsvImported;

/**
 * Input for `doImportClassCategoryCsv` — an admin bulk-uploads the 規格分類
 * CSV (Hard ActionRedirect completion). ALPS marks it `unsafe`. The
 * parse/validate/persist pipeline is isolated in
 * {@see \MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface}.
 */
#[Be(ClassCategoryCsvImported::class)]
final readonly class ImportClassCategoryCsvInput
{
    /** @psalm-taint-source input $csv */
    public function __construct(
        public string $csv = '',
    ) {
    }
}
