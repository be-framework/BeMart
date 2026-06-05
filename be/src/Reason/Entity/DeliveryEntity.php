<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Admin-side delivery-method master row — projection of EC-CUBE
 * dtb_delivery (Wave 9θ shop settings slice).
 *
 *   - deliveryId   : opaque server-generated identifier
 *   - deliveryName : display name (e.g. "ヤマト宅急便")
 *   - visible      : true = surfaced at checkout, false = soft hidden
 *
 * 厳密移植 alignment: dtb_delivery has NO fee columns. The base fee is
 * per-prefecture data in dtb_delivery_fee and the free-shipping
 * threshold is the global dtb_base_info.delivery_free_amount value.
 * Both `feeBase` and `freeAmount` were BeMart Phase-1 simplifications
 * that drifted from the schema and have been dropped; per-prefecture
 * DeliveryFee rows and the global threshold are deferred to a later
 * phase (separate models). DeliveryTime slots and DeliveryDuration
 * estimates from the ALPS profile remain out of scope as well.
 */
final readonly class DeliveryEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public string $deliveryId;
    public string $deliveryName;
    public bool $visible;

    public function __construct(
        string $deliveryId,
        string $deliveryName,
        bool|int|string $visible,
    ) {
        $this->deliveryId = $deliveryId;
        $this->deliveryName = $deliveryName;
        $this->visible = (bool) $visible;
    }
}
