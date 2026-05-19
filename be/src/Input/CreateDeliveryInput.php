<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\DeliveryCreated;

/**
 * Input for doCreateDelivery — admin adds a new delivery-method master
 * row (Wave 9θ).
 *
 *   CreateDeliveryInput → DeliveryCreated (Direct, admin AUTHZ)
 *
 * `deliveryId` is server-generated.
 */
#[Be(DeliveryCreated::class)]
final readonly class CreateDeliveryInput
{
    /**
     * @psalm-taint-source input $deliveryName
     * @psalm-taint-source input $feeBase
     * @psalm-taint-source input $freeAmount
     * @psalm-taint-source input $visible
     */
    public function __construct(
        public string $deliveryName,
        public int $feeBase = 0,
        public int|null $freeAmount = null,
        public bool $visible = true,
    ) {
    }
}
