<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ClassNameCsvExported;

/**
 * Input for `goExportClassName` — an admin downloads the 規格名 master as
 * an EC-CUBE-format CSV (Hard ActionRedirect completion). `safe` (a
 * read-through download); the encoding/headers are isolated in
 * {@see \MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface}.
 */
#[Be(ClassNameCsvExported::class)]
final readonly class ExportClassNameInput
{
    public function __construct()
    {
    }
}
