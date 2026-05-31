<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ContentCssUpdated;

/**
 * Input for `doUpdateContentCss` — an admin saves the customize CSS (Hard
 * ActionRedirect completion). ALPS marks it `idempotent`. The public-file
 * write is isolated in
 * {@see \MyVendor\BeMart\Be\Reason\Service\CustomizeAssetWriterInterface}.
 */
#[Be(ContentCssUpdated::class)]
final readonly class UpdateContentCssInput
{
    /** @psalm-taint-source input $css */
    public function __construct(
        public string $css = '',
    ) {
    }
}
