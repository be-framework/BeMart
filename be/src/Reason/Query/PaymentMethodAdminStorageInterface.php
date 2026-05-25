<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\PaymentMethodAdminEntity;

/**
 * Admin payment-method master — unified Query + Command (Wave 9θ).
 *
 * Same convention as {@see ClassNameStorageInterface}:
 *   - list(): every master row, sorted by id for stable display
 *   - getById(paymentId): single-row lookup
 *   - put(payment): upsert (create / replace)
 *   - remove(paymentId): drop (silent no-op on miss)
 *
 * NOTE: the customer-side
 * {@see \MyVendor\BeMart\Be\Reason\Service\PaymentMethodFactoryInterface}
 * is intentionally NOT folded into this interface. The factory is a
 * dispatcher of behaviour (how to verify a payment) keyed by id; this
 * storage is the editable master. Production wiring will eventually
 * back both off the same SQL table without merging the two interfaces.
 */
interface PaymentMethodAdminStorageInterface
{
    /** @return list<PaymentMethodAdminEntity> */
    public function list(): array;

    public function getById(string $paymentId): PaymentMethodAdminEntity|null;

    public function put(PaymentMethodAdminEntity $payment): void;

    public function remove(string $paymentId): void;
}
