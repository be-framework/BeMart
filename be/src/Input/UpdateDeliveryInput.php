<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\DeliveryUpdated;

/**
 * Input for doUpdateDelivery — admin edits a delivery-method master row
 * (Wave 9θ).
 *
 *   UpdateDeliveryInput → DeliveryUpdated (Direct, idempotent)
 *
 * Null body fields preserve the current persisted value.
 */
#[Be(DeliveryUpdated::class)]
final readonly class UpdateDeliveryInput
{
    /**
     * @psalm-taint-source input $deliveryId
     * @psalm-taint-source input $deliveryName
     * @psalm-taint-source input $feeBase
     * @psalm-taint-source input $freeAmount
     * @psalm-taint-source input $visible
     */
    public function __construct(
        public string $deliveryId,
        public string|null $deliveryName = null,
        public int|null $feeBase = null,
        public int|null $freeAmount = null,
        public bool|null $visible = null,
    ) {
    }
}
