<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\BlockCreated;

/**
 * Input for doCreateBlock — admin creates a UI block (Wave 9).
 * New blocks are always deletable (blockDeletable=true).
 */
#[Be(BlockCreated::class)]
final readonly class CreateBlockInput
{
    /**
     * @psalm-taint-source input $blockName
     * @psalm-taint-source input $blockFileName
     */
    public function __construct(
        public string $blockName,
        public string $blockFileName,
    ) {
    }
}
