<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\ShippingAddressEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ShippingAddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin shipping address updated — Final, proof an admin overwrote
 * the order's shipping address fields directly.
 *
 *   AdminUpdateShippingAddressInput → AdminShippingAddressUpdated
 *                                      (Direct, unsafe)
 *
 * AUTHZ — cross-firewall:
 *   1. No admin session     → 403
 *   2. Unknown orderNo      → 404
 *
 * Either creates or overwrites the row keyed by orderNo. The Final
 * does NOT consult any pre-existing shipping address row — the admin's
 * supplied fields are authoritative (per the ALPS doc: "注文手続き中の
 * お届け先情報（住所・氏名・連絡先）を更新する").
 *
 * Mass-assignment safety: only the 8 shipping-address columns are
 * editable. Order header (orderNo / customerId / etc.) is reused
 * verbatim from the read-side lookup; the admin cannot mutate dtb_order
 * via this transition.
 */
final readonly class AdminShippingAddressUpdated
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
        #[Input] string $name01,
        #[Input] string $name02,
        #[Input] string $postalCode,
        #[Input] int $pref,
        #[Input] string $addr01,
        #[Input] string $addr02,
        #[Input] string $phoneNumber,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] OrderQueryInterface $orderQuery,
        #[Inject] ShippingAddressStorageInterface $shippingAddresses,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $order = $orderQuery->byOrderNo($orderNo);
        if ($order === null) {
            throw new OrderNotFoundException();
        }

        $entity = new ShippingAddressEntity(
            orderNo: $order->orderNo,
            name01: $name01,
            name02: $name02,
            postalCode: $postalCode,
            pref: $pref,
            addr01: $addr01,
            addr02: $addr02,
            phoneNumber: $phoneNumber,
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
