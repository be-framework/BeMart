<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ContentJsUpdated;

/**
 * Input for `doUpdateContentJs` — an admin saves the customize JS (Hard
 * ActionRedirect completion). ALPS marks it `idempotent`. The public-file
 * write is isolated in
 * {@see \MyVendor\BeMart\Be\Reason\Service\CustomizeAssetWriterInterface}.
 */
#[Be(ContentJsUpdated::class)]
final readonly class UpdateContentJsInput
{
    /** @psalm-taint-source input $js */
    public function __construct(
        public string $js = '',
    ) {
    }
}
