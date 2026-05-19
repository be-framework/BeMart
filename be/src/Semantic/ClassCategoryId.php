<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

final class ClassCategoryId
{
    #[Validate]
    public function validate(string|null $classCategoryId): void
    {
        // Type assertion only — generator is the contract.
    }
}
