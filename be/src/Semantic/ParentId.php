<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Parent category id — nullable foreign key into the same category
 * table. null = root-level node. Type assertion only; the generator is
 * the contract for existence checks (and the Final asserts a referenced
 * parent resolves before persistence).
 */
final class ParentId
{
    #[Validate]
    public function validate(string|null $parentId): void
    {
        // Type assertion only.
    }
}
