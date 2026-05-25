<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\AddressNotFoundException;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\ShippingAddressEntity;
use MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ShippingAddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin shipping address selected — Final, proof an admin set one
 * order's delivery target to an existing address-book entry.
 *
 *   AdminSelectShippingAddressInput → AdminShippingAddressSelected
 *                                      (Direct, unsafe)
 *
 * AUTHZ — cross-firewall + ownership:
 *   1. No admin session                         → 403
 *   2. Unknown orderNo                          → 404
 *   3. Unknown addressId / wrong customer       → 404
 *
 * The third clause keeps the ownership semantics of the address-book
 * intact even when an admin is the caller: addresses are scoped to a
 * single customer. An admin attaching customer-B's address to
 * customer-A's order would create a referential mess; we 404 rather
 * than silently allow it. The "404 over 403" choice here is anti-
 * enumeration: an admin caller learns no more about which addressIds
 * exist than they would by guessing.
 */
final readonly class AdminShippingAddressSelected
{
    public string $orderNo;
    public string $name01;
    public string $name02;
    public string $postalCode;
    public int $pref;
    public string $addr01;
    public string $addr02;
    public string $phoneNumber;

    public function __construct(
        #[Input] string $orderNo,
        #[Input] string $addressId,
        #[Inject] AdminSession $adminSession,
        #[Inject] OrderQueryInterface $orderQuery,
        #[Inject] AddressStorageInterface $addressStorage,
        #[Inject] ShippingAddressStorageInterface $shippingAddresses,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $order = $orderQuery->byOrderNo($orderNo);
        if ($order === null) {
            throw new OrderNotFoundException();
        }

        $address = $addressStorage->item($addressId);
        if ($address === null || $address->customerId !== $order->customerId) {
            throw new AddressNotFoundException();
        }

        $entity = new ShippingAddressEntity(
            orderNo: $order->orderNo,
            name01: $address->name01,
            name02: $address->name02,
            postalCode: $address->postalCode,
            pref: $address->pref,
            addr01: $address->addr01,
            addr02: $address->addr02,
            phoneNumber: $address->phoneNumber ?? '',
        );

        $shippingAddresses->put($entity);

        $this->orderNo = $entity->orderNo;
        $this->name01 = $entity->name01;
        $this->name02 = $entity->name02;
        $this->postalCode = $entity->postalCode;
        $this->pref = $entity->pref;
        $this->addr01 = $entity->addr01;
        $this->addr02 = $entity->addr02;
        $this->phoneNumber = $entity->phoneNumber;
    }
}
