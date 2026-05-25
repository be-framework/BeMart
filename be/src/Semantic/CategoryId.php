<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Category id — server-derived. Provided by
 * CategoryIdProvider (Wave 7). Type assertion only — the
 * provider itself is the contract (opaque hex string).
 */
final class CategoryId
{
    #[Validate]
    public function validate(string|null $categoryId): void
    {
        // Type assertion only — provider is the contract.
    }
}
