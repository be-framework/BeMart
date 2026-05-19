<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\DeliveryEntity;
use MyVendor\BeMart\Be\Reason\Query\DeliveryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\DeliveryIdGeneratorInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Delivery method created — Final, proof a new delivery-method master
 * row was persisted (Wave 9θ).
 *
 *   CreateDeliveryInput → DeliveryCreated (Direct, admin AUTHZ)
 */
final readonly class DeliveryCreated
{
    public string $deliveryId;
    public string $deliveryName;
    public int $feeBase;
    public int|null $freeAmount;
    public bool $visible;

    public function __construct(
        #[Input] string $deliveryName,
        #[Input] int $feeBase,
        #[Input] int|null $freeAmount,
        #[Input] bool $visible,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] DeliveryStorageInterface $deliveries,
        #[Inject] DeliveryIdGeneratorInterface $idGenerator,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $entity = new DeliveryEntity(
            deliveryId: $idGenerator->generate(),
            deliveryName: $deliveryName,
            feeBase: $feeBase,
            freeAmount: $freeAmount,
            visible: $visible,
        );

        $deliveries->put($entity);

        $this->deliveryId = $entity->deliveryId;
        $this->deliveryName = $entity->deliveryName;
        $this->feeBase = $entity->feeBase;
        $this->freeAmount = $entity->freeAmount;
        $this->visible = $entity->visible;
    }
}
