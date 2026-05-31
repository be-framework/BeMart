<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\TemplateDeleted;

/**
 * Input for `doDeleteTemplate` — an admin deletes a design template
 * (Hard ActionRedirect completion). ALPS marks it `idempotent`. The
 * file-removal side-effect is isolated in
 * {@see \MyVendor\BeMart\Be\Reason\Service\TemplateCompatibilityInterface}.
 */
#[Be(TemplateDeleted::class)]
final readonly class DeleteTemplateInput
{
    /** @psalm-taint-source input $templateId */
    public function __construct(
        public string $templateId,
    ) {
    }
}
