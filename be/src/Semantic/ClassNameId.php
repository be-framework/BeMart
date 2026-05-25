<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

final class ClassNameId
{
    #[Validate]
    public function validate(string|null $classNameId): void
    {
        // Type assertion only — generator is the contract.
    }
}
