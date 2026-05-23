<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PurchaseTotals;

/**
 * EC-CUBE PurchaseFlow (shopping flow) — tax / shipping / point aggregation.
 *
 * Wraps the imperative `executePurchaseFlow($Order)` call into a pure
 * function-shaped Reason: in → Order, out → totals. The Pilot fake is
 * deterministic; a production binding would call into EC-CUBE 4.3's
 * Eccube\Service\PurchaseFlow\PurchaseFlow.
 */
interface PurchaseFlowInterface
{
    public function apply(OrderEntity $preOrder): PurchaseTotals;
}
