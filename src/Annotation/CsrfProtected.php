<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class CsrfProtected
{
    public function __construct(
        public string $bodyField = 'csrfToken',
    ) {
    }
}
