<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ClassCategoryDeleted;

/**
 * Input for doDeleteClassCategory — admin removes a product variant
 * value (Wave 7).
 *
 *   DeleteClassCategoryInput → ClassCategoryDeleted (Direct,
 *                                                    idempotent)
 */
#[Be(ClassCategoryDeleted::class)]
final readonly class DeleteClassCategoryInput
{
    /**
     * @psalm-taint-source input $classCategoryId
     */
    public function __construct(
        public string $classCategoryId,
    ) {
    }
}
