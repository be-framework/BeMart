<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\BlockDeleted;

/**
 * Input for doDeleteBlock — admin removes a UI block (Wave 9, idempotent).
 * System-standard blocks (blockDeletable=false) cannot be deleted.
 */
#[Be(BlockDeleted::class)]
final readonly class DeleteBlockInput
{
    /**
     * @psalm-taint-source input $blockId
     */
    public function __construct(
        public string $blockId,
    ) {
    }
}
