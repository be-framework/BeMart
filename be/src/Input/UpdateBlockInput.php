<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\BlockUpdated;

/**
 * Input for doUpdateBlock — admin edits a UI block (Wave 9, idempotent).
 */
#[Be(BlockUpdated::class)]
final readonly class UpdateBlockInput
{
    /**
     * @psalm-taint-source input $blockId
     * @psalm-taint-source input $blockName
     * @psalm-taint-source input $blockFileName
     */
    public function __construct(
        public string $blockId,
        public string|null $blockName = null,
        public string|null $blockFileName = null,
    ) {
    }
}
