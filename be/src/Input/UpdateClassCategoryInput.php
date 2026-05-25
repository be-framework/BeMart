<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ClassCategoryUpdated;

/**
 * Input for doUpdateClassCategory — admin renames a product variant
 * value (Wave 7).
 *
 *   UpdateClassCategoryInput → ClassCategoryUpdated (Direct,
 *                                                    idempotent)
 */
#[Be(ClassCategoryUpdated::class)]
final readonly class UpdateClassCategoryInput
{
    /**
     * @psalm-taint-source input $classCategoryId
     * @psalm-taint-source input $classCategoryName
     */
    public function __construct(
        public string $classCategoryId,
        public string|null $classCategoryName = null,
    ) {
    }
}
