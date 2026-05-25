<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\DeliveryEntity;
use MyVendor\BeMart\Be\Reason\Query\DeliveryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;

use function array_map;
use function count;

/**
 * Admin delivery-method list fetched — Final, the back-office view of
 * every delivery-method master row (Wave 9θ).
 *
 *   GetAdminDeliveryListInput → AdminDeliveryListFetched
 *     (Direct, safe read, admin AUTHZ)
 */
final readonly class AdminDeliveryListFetched
{
    public int $count;

    /**
     * @var list<array{
     *     deliveryId: string,
     *     deliveryName: string,
     *     visible: bool,
     * }>
     */
    public array $deliveries;

    public function __construct(
        #[Inject] AdminSession $adminSession,
        #[Inject] DeliveryStorageInterface $deliveries,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $deliveries->list();

        $this->count = count($rows);
        $this->deliveries = array_map(
            static fn (DeliveryEntity $row): array => [
                'deliveryId' => $row->deliveryId,
                'deliveryName' => $row->deliveryName,
                'visible' => $row->visible,
            ],
            $rows,
        );
    }
}
