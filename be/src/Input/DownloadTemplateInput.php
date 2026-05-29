<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\TemplateDownloaded;

/**
 * Input for `doDownloadTemplate` — an admin downloads a design template
 * as a zip (Hard ActionRedirect completion). ALPS marks it `unsafe`. The
 * zip packing + headers are isolated in
 * {@see \MyVendor\BeMart\Be\Reason\Service\TemplateCompatibilityInterface}.
 */
#[Be(TemplateDownloaded::class)]
final readonly class DownloadTemplateInput
{
    /** @psalm-taint-source input $templateId */
    public function __construct(
        public string $templateId,
    ) {
    }
}
