<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\TemplateSelected;

/**
 * Input for `doSelectTemplate` — an admin activates a design template
 * (Hard ActionRedirect completion). ALPS marks it `idempotent`. The
 * asset-deploy side-effect is isolated in
 * {@see \MyVendor\BeMart\Be\Reason\Service\TemplateCompatibilityInterface}.
 */
#[Be(TemplateSelected::class)]
final readonly class SelectTemplateInput
{
    /** @psalm-taint-source input $templateId */
    public function __construct(
        public string $templateId,
    ) {
    }
}
