<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\DeliveryNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\DeliveryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Delivery method deleted — Final, proof one master row was removed
 * (Wave 9θ).
 *
 *   DeleteDeliveryInput → DeliveryDeleted (Direct, idempotent)
 *
 * Same soft-delete caveat as {@see PaymentMethodAdminDeleted}: the
 * in-memory store drops the row unconditionally; production parity
 * (visible = false) is Phase 2.
 */
final readonly class DeliveryDeleted
{
    public string $deliveryId;

    public function __construct(
        #[Input] string $deliveryId,
        #[Inject] AdminSession $adminSession,
        #[Inject] DeliveryStorageInterface $deliveries,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($deliveries->item($deliveryId) === null) {
            throw new DeliveryNotFoundException();
        }

        $deliveries->delete($deliveryId);

        $this->deliveryId = $deliveryId;
    }
}
