<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ShippingAddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Tracking number updated — Final, proof an admin wrote the shipping
 * tracking number onto an order (`doUpdateTrackingNumber`).
 *
 *   UpdateTrackingNumberInput → TrackingNumberUpdated  (Direct, idempotent)
 *
 * AUTHZ — cross-firewall ladder (same as the rest of the admin Order
 * Finals, e.g. {@see AdminShippingAddressUpdated}):
 *   1. No admin session   → UnauthorizedAdminAccessException  (403)
 *   2. Unknown orderNo    → OrderNotFoundException            (404)
 *
 * The order's existence is the gate (via {@see OrderQueryInterface});
 * the tracking number is then written onto the order's dtb_shipping row
 * by {@see ShippingAddressStorageInterface::tracking}. Only
 * the `tracking_number` column is touched — the shipping address fields
 * are out of reach of this transition (mass-assignment discipline).
 *
 * Idempotency: ALPS marks this `idempotent` — re-sending the same
 * number leaves the row in the same state.
 */
final readonly class TrackingNumberUpdated
{
    public string $orderNo;
    public string $trackingNumber;

    public function __construct(
        #[Input] string $orderNo,
        #[Input] string $trackingNumber,
        #[Inject] AdminSession $adminSession,
        #[Inject] OrderQueryInterface $orderQuery,
        #[Inject] ShippingAddressStorageInterface $shippingAddresses,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $order = $orderQuery->byOrderNo($orderNo);
        if ($order === null) {
            throw new OrderNotFoundException();
        }

        $shippingAddresses->updateTrackingNumber($order->orderNo, $trackingNumber);

        $this->orderNo = $order->orderNo;
        $this->trackingNumber = $trackingNumber;
    }
}
