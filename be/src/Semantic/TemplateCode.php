<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * TemplateCode — the machine code of a newly installed design template
 * (doInstallTemplate). Free-form identifier; the ontology names it.
 */
final class TemplateCode
{
    #[Validate]
    public function validate(string $templateCode): void
    {
    }
}
