<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Css — the customize-CSS body submitted on the admin CSS editor
 * (doUpdateContentCss). Free-form stylesheet text; the public-file write
 * is a boundary-service concern, so the ontology only names the variable.
 */
final class Css
{
    #[Validate]
    public function validate(string $css): void
    {
    }
}
