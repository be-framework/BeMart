<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * TemplateName — the display name of a newly installed design template
 * (doInstallTemplate). Free-form label; the ontology names it.
 */
final class TemplateName
{
    #[Validate]
    public function validate(string $templateName): void
    {
    }
}
