<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Admin-side delivery-method master row — projection of EC-CUBE
 * dtb_delivery (Wave 9θ shop settings slice).
 *
 *   - deliveryId   : opaque server-generated identifier
 *   - deliveryName : display name (e.g. "ヤマト宅急便")
 *   - feeBase      : default base fee in JPY (>= 0). The per-prefecture
 *                    DeliveryFee table refines this, but for Phase 1 the
 *                    admin master only carries the base.
 *   - freeAmount   : order-total threshold above which delivery becomes
 *                    free; null = never free.
 *   - visible      : true = surfaced at checkout, false = soft hidden
 *
 * Per-prefecture DeliveryFee rows, DeliveryTime slots and
 * DeliveryDuration estimates from the ALPS profile are deliberately
 * deferred — Phase 2 will model them when a real consumer needs them.
 */
final readonly class DeliveryEntity
{
    public function __construct(
        public string $deliveryId,
        public string $deliveryName,
        public int $feeBase,
        public int|null $freeAmount,
        public bool $visible,
    ) {
    }
}
