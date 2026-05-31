<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Enabled — an explicit on/off target state for an idempotent toggle
 * (e.g. doToggleMaintenance). A bool needs no further constraint; this
 * names the variable in the ontology.
 */
final class Enabled
{
    #[Validate]
    public function validate(bool $enabled): void
    {
    }
}
