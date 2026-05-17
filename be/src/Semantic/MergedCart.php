<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Reason\Entity\CartEntity;

/**
 * Merged cart — the in-memory CartEntity after item merge in Stage 2 (CartMerged Being).
 *
 * Composite-type assertion: the CartEntity type itself is the contract; field-level
 * constraints (cartKey format, totalPrice range, etc.) are guarded by their own
 * Semantic classes on the surrounding scalars.
 */
final class MergedCart
{
    #[Validate]
    public function validate(CartEntity $mergedCart): void
    {
        // Type assertion only — CartEntity composite is the contract.
    }
}
