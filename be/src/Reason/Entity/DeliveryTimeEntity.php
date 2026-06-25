<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Delivery time-slot row — projection of EC-CUBE dtb_delivery_time.
 *
 * A delivery method owns an ordered list of お届け時間 slots
 * (e.g. 午前中 / 14:00-16:00 / 16:00-18:00). The checkout page renders
 * these as the お届け時間 <select> options for the chosen 配送方法.
 *
 *   - timeId       : opaque server-generated identifier
 *   - deliveryTime : display label (the slot text)
 *   - visible      : true = surfaced at checkout, false = soft hidden
 */
final readonly class DeliveryTimeEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public bool $visible;

    public function __construct(
        public string $timeId,
        public string $deliveryTime,
        bool|int|string $visible = true,
    ) {
        $this->visible = (bool) $visible;
    }
}
