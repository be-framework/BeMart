<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\DeliveryDeleted;

/**
 * Input for doDeleteDelivery — admin removes a delivery-method master
 * row (Wave 9θ).
 *
 *   DeleteDeliveryInput → DeliveryDeleted (Direct, idempotent)
 */
#[Be(DeliveryDeleted::class)]
final readonly class DeleteDeliveryInput
{
    /**
     * @psalm-taint-source input $deliveryId
     */
    public function __construct(
        public string $deliveryId,
    ) {
    }
}
