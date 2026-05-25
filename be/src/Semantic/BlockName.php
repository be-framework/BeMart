<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Block display name — EC-CUBE 4.3 dtb_block.block_name (Wave 9).
 */
final class BlockName
{
    #[Validate]
    public function validate(string|null $blockName): void
    {
        // Type assertion only.
    }
}
