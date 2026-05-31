<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ClassNameCsvImported;

/**
 * Input for `doImportClassNameCsv` — an admin bulk-uploads the 規格名 CSV
 * (Hard ActionRedirect completion). ALPS marks it `unsafe`. The
 * parse/validate/persist pipeline is isolated in
 * {@see \MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface}.
 */
#[Be(ClassNameCsvImported::class)]
final readonly class ImportClassNameCsvInput
{
    /** @psalm-taint-source input $csv */
    public function __construct(
        public string $csv = '',
    ) {
    }
}
