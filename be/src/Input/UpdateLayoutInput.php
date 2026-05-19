<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\LayoutUpdated;

/**
 * Input for doUpdateLayout — admin edits a layout's name (Wave 9,
 * idempotent). Block-placement editing is Phase 2 scope.
 */
#[Be(LayoutUpdated::class)]
final readonly class UpdateLayoutInput
{
    /**
     * @psalm-taint-source input $layoutId
     * @psalm-taint-source input $layoutName
     */
    public function __construct(
        public string $layoutId,
        public string|null $layoutName = null,
    ) {
    }
}
