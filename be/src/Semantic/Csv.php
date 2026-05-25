<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Raw CSV body — type assertion only. The shape (header row + comma-
 * delimited rows) is the contract of the upstream form; this semantic
 * registers the parameter name in the ontology so the Be Framework
 * does not surface an "unknown semantic" notice. Real format / row
 * validation lives in the Final once the Phase 2 importer ships.
 */
final class Csv
{
    #[Validate]
    public function validate(string|null $csv): void
    {
        // Phase 2: validate columns + row encoding here.
    }
}
