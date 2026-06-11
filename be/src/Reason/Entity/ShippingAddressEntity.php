<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Persisted shipping address for one finalized order (mirrors EC-CUBE's
 * dtb_shipping — one row per order in the simple-shipping case; multi-
 * shipping is Phase 2).
 *
 * Introduced by Wave 9η (admin shipping-address management). Keyed by
 * `orderNo` so the admin-side `doSelectShippingAddress` /
 * `doUpdateShippingAddress` flows can attach / edit an address against
 * an existing finalized order without polluting {@see FinalizedOrderEntity}
 * (which intentionally keeps the dtb_order column set narrow). The choice
 * mirrors EC-CUBE's table separation — order header lives in dtb_order,
 * delivery target lives in dtb_shipping — so the Phase 2 migration to
 * Ray.MediaQuery can map this entity 1:1 onto dtb_shipping rows.
 *
 * `trackingNumber` is a read-projection field for CSV export. It is not
 * written through the address update transition; shipment fulfilment keeps
 * using the narrow `updateTrackingNumber` command.
 */
final readonly class ShippingAddressEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public function __construct(
        public string $orderNo,
        public string $name01,
        public string $name02,
        public string $postalCode,
        public int $pref,
        public string $addr01,
        public string $addr02,
        public string $phoneNumber,
        public string $trackingNumber = '',
    ) {
    }
}
