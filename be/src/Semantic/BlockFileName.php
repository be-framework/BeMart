<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Block template filename — EC-CUBE 4.3 dtb_block.file_name (Wave 9).
 */
final class BlockFileName
{
    #[Validate]
    public function validate(string|null $blockFileName): void
    {
        // Type assertion only.
    }
}
