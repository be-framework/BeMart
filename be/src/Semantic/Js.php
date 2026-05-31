<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Js — the customize-JS body submitted on the admin JS editor
 * (doUpdateContentJs). Free-form script text; the public-file write is a
 * boundary-service concern, so the ontology only names the variable.
 */
final class Js
{
    #[Validate]
    public function validate(string $js): void
    {
    }
}
