<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ClassCategoryCsvExported;

/**
 * Input for `goExportClassCategory` — an admin downloads the 規格分類
 * master (optionally scoped to one 規格名) as an EC-CUBE-format CSV (Hard
 * ActionRedirect completion). `safe`; the encoding/headers are isolated
 * in {@see \MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface}.
 */
#[Be(ClassCategoryCsvExported::class)]
final readonly class ExportClassCategoryInput
{
    /** @psalm-taint-source input $classNameId */
    public function __construct(
        public string|null $classNameId = null,
    ) {
    }
}
