<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\ShippingAddressEntity;

interface OrderPdfCompatibilityInterface
{
    /**
     * @param non-empty-list<FinalizedOrderEntity>             $orders
     * @param array<string, list<OrderItemEntity>>             $itemsByOrderNo
     * @param array<string, ShippingAddressEntity|null>        $shippingByOrderNo
     */
    public function render(
        array $orders,
        array $itemsByOrderNo,
        array $shippingByOrderNo,
    ): OrderPdfDocument;
}
