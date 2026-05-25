<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CategoryDeleted;

/**
 * Input for doDeleteCategory — admin removes a catalog category
 * (Wave 7).
 *
 *   DeleteCategoryInput → CategoryDeleted (Direct, idempotent)
 */
#[Be(CategoryDeleted::class)]
final readonly class DeleteCategoryInput
{
    /**
     * @psalm-taint-source input $categoryId
     */
    public function __construct(
        public string $categoryId,
    ) {
    }
}
