<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\DeliveryEntity;
use MyVendor\BeMart\Be\Reason\Query\DeliveryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Provider\DeliveryIdProvider;
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
    public bool $visible;

    public function __construct(
        #[Input] string $deliveryName,
        #[Input] bool $visible,
        #[Inject] AdminSession $adminSession,
        #[Inject] DeliveryStorageInterface $deliveries,
        #[Inject] DeliveryIdProvider $ids,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $entity = new DeliveryEntity(
            deliveryId: $ids->get(),
            deliveryName: $deliveryName,
            visible: $visible,
        );

        $deliveries->put($entity);

        $this->deliveryId = $entity->deliveryId;
        $this->deliveryName = $entity->deliveryName;
        $this->visible = $entity->visible;
    }
}
