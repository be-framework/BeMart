<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CategoryUpdated;

/**
 * Input for doUpdateCategory — admin edits a category in place
 * (Wave 7).
 *
 *   UpdateCategoryInput → CategoryUpdated (Direct, admin AUTHZ,
 *                                          idempotent)
 *
 * Merge semantics (Pilot 8 convention): nullable editable fields
 * leave the persisted value untouched. `categoryId` is the lookup key
 * and is required.
 */
#[Be(CategoryUpdated::class)]
final readonly class UpdateCategoryInput
{
    /**
     * @psalm-taint-source input $categoryId
     * @psalm-taint-source input $categoryName
     * @psalm-taint-source input $parentId
     * @psalm-taint-source input $sortNo
     */
    public function __construct(
        public string $categoryId,
        public string|null $categoryName = null,
        public int|null $sortNo = null,
        public string|null $parentId = null,
    ) {
    }
}
