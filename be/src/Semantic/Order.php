<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;

/**
 * Order — the pre-order Order aggregate carried through Pilot 3's chain
 * (PreOrderResolved → PurchaseFlowApplied → PaymentVerified).
 *
 * Composite-type assertion: the OrderEntity type itself is the contract;
 * field-level constraints (preOrderId format, etc.) are guarded by their
 * own Semantic classes on the surrounding scalars.
 */
final class Order
{
    #[Validate]
    public function validate(OrderEntity $order): void
    {
        // Type assertion only — OrderEntity composite is the contract.
    }
}
