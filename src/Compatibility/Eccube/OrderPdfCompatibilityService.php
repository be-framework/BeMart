<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Exception\OrderPdfNotSupportedException;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\ShippingAddressEntity;
use MyVendor\BeMart\Be\Reason\Service\OrderPdfCompatibilityInterface;
use MyVendor\BeMart\Be\Reason\Service\OrderPdfDocument;
use Override;

/**
 * BeMart-side adapter for EC-CUBE-compatible order PDF generation.
 *
 * The GPL-licensed EC-CUBE OrderPdfRenderer has been removed.
 * This implementation unconditionally throws OrderPdfNotSupportedException;
 * the Resource layer maps it to HTTP 501 Not Implemented.
 */
final readonly class OrderPdfCompatibilityService implements OrderPdfCompatibilityInterface
{
    /**
     * @param non-empty-list<FinalizedOrderEntity>             $orders
     * @param array<string, list<OrderItemEntity>>             $itemsByOrderNo
     * @param array<string, ShippingAddressEntity|null>        $shippingByOrderNo
     */
    #[Override]
    public function render(
        array $orders,
        array $itemsByOrderNo,
        array $shippingByOrderNo,
    ): OrderPdfDocument {
        throw new OrderPdfNotSupportedException();
    }
}
