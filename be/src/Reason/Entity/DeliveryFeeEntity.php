<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Delivery base-fee row — projection of EC-CUBE dtb_delivery_fee.
 *
 * dtb_delivery has no fee column; the 送料 is per-prefecture data in
 * dtb_delivery_fee(delivery_id, pref_id, fee). The checkout page needs a
 * single representative 送料 for the method before a shipping prefecture
 * is locked in, so {@see \MyVendor\BeMart\Be\Reason\Query\DeliveryStorageInterface::baseFee}
 * returns the minimum fee across prefectures as this one-field row.
 *
 *   - fee : base 送料 in JPY (>= 0)
 */
final readonly class DeliveryFeeEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public function __construct(
        public int $fee = 0,
    ) {
    }
}
