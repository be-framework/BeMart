<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\PaymentMethodAdminEntity;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Admin payment-method master — unified Query + Command (Wave 9θ).
 *
 * Same convention as {@see ClassNameStorageInterface}:
 *   - list(): every master row, sorted by id for stable display
 *   - item(paymentId): single-row lookup
 *   - put(payment): upsert (create / replace)
 *   - delete(paymentId): drop (silent no-op on miss)
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
    #[DbQuery('tpayment_list')]
    public function list(): array;

    #[DbQuery('tpayment_get')]
    public function item(string $paymentId): PaymentMethodAdminEntity|null;

    #[DbQuery('tpayment_put')]
    public function put(PaymentMethodAdminEntity $payment): void;

    #[DbQuery('tpayment_remove')]
    public function delete(string $paymentId): void;

    /**
     * Generic `doSortNoMove` — rewrites the storage-only `sort_no`
     * column of dtb_payment. sort_no is NOT part of the 6-field
     * PaymentMethodAdminEntity projection; this edits the column
     * directly. A miss is a silent no-op (same shape as `delete`).
     */
    #[DbQuery('tpayment_reorder')]
    public function reorder(string $paymentId, int $sortNo): void;

    /**
     * Generic `doToggleVisible` — rewrites the `visible` column of
     * dtb_payment. Unlike sort_no, `visible` IS projected onto
     * {@see PaymentMethodAdminEntity}, so the Fake also rebuilds the
     * cached entity so its `list()` projection stays consistent.
     */
    #[DbQuery('tpayment_visible')]
    public function setVisible(string $paymentId, bool $visible): void;
}
