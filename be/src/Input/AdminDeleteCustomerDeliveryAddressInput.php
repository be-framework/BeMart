<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminCustomerDeliveryAddressDeleted;

/**
 * Input for doDeleteCustomerDeliveryAddress — admin removes one row from
 * an arbitrary customer's address book (Wave 3, customer-address-write).
 *
 *   AdminDeleteCustomerDeliveryAddressInput
 *       → AdminCustomerDeliveryAddressDeleted   (Direct, idempotent)
 *
 * Actor scope: `customerId` is the route-param Input (admin firewall),
 * `addressId` the target row. The Final verifies ownership
 * (entity.customerId === route customerId) before deleting, mirroring the
 * EC-CUBE CustomerDeliveryEditController::delete() ownership guard.
 */
#[Be(AdminCustomerDeliveryAddressDeleted::class)]
final readonly class AdminDeleteCustomerDeliveryAddressInput
{
    /**
     * @psalm-taint-source input $customerId
     * @psalm-taint-source input $addressId
     */
    public function __construct(
        public string $customerId,
        public string $addressId,
    ) {
    }
}
