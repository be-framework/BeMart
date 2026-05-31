<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * TemplateId — the design-template identifier targeted by select /
 * delete / download. Existence is checked in the Final via the template
 * boundary; the ontology only names the variable.
 */
final class TemplateId
{
    #[Validate]
    public function validate(string $templateId): void
    {
    }
}
