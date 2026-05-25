<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\DeliveryNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\DeliveryEntity;
use MyVendor\BeMart\Be\Reason\Query\DeliveryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Delivery method updated — Final, proof one master row was edited in
 * place (Wave 9θ).
 *
 *   UpdateDeliveryInput → DeliveryUpdated (Direct, idempotent)
 *
 * AUTHZ ladder:
 *   1. No admin session → UnauthorizedAdminAccessException (403)
 *   2. Unknown id       → DeliveryNotFoundException        (404)
 */
final readonly class DeliveryUpdated
{
    public string $deliveryId;
    public string $deliveryName;
    public int $feeBase;
    public int|null $freeAmount;
    public bool $visible;

    public function __construct(
        #[Input] string $deliveryId,
        #[Input] string|null $deliveryName,
        #[Input] int|null $feeBase,
        #[Input] int|null $freeAmount,
        #[Input] bool|null $visible,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] DeliveryStorageInterface $deliveries,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $current = $deliveries->getById($deliveryId);
        if ($current === null) {
            throw new DeliveryNotFoundException();
        }

        $merged = new DeliveryEntity(
            deliveryId: $current->deliveryId,
            deliveryName: $deliveryName ?? $current->deliveryName,
            feeBase: $feeBase ?? $current->feeBase,
            freeAmount: $freeAmount ?? $current->freeAmount,
            visible: $visible ?? $current->visible,
        );

        $deliveries->put($merged);

        $this->deliveryId = $merged->deliveryId;
        $this->deliveryName = $merged->deliveryName;
        $this->feeBase = $merged->feeBase;
        $this->freeAmount = $merged->freeAmount;
        $this->visible = $merged->visible;
    }
}
