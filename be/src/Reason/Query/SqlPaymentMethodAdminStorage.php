<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\PaymentMethodAdminEntity;
use Override;

use function ctype_digit;

final class SqlPaymentMethodAdminStorage implements PaymentMethodAdminStorageInterface
{
    public function __construct(private readonly InternalDbQueryInterface $db) {}

    /** @return list<PaymentMethodAdminEntity> */
    #[Override]
    public function list(): array
    {
        return array_map($this->hydrate(...), $this->db->tpayment_list());
    }

    #[Override]
    public function getById(string $paymentId): PaymentMethodAdminEntity|null
    {
        if (! ctype_digit($paymentId)) {
            return null;
        }
        $row = $this->db->tpayment_get(id: (int) $paymentId);
        return $row === null ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(PaymentMethodAdminEntity $payment): void
    {
        if (! ctype_digit($payment->paymentId)) {
            return;
        }
        $id = (int) $payment->paymentId;
        if ($this->db->tpayment_exists(id: $id) === null) {
            $this->db->tpayment_insert(
                id: $id,
                paymentMethod: $payment->paymentMethodName,
                charge: $payment->charge,
                ruleMin: $payment->ruleMin,
                ruleMax: $payment->ruleMax,
                visible: (int) $payment->visible,
            );

            return;
        }

        $this->db->tpayment_update(
            id: $id,
            paymentMethod: $payment->paymentMethodName,
            charge: $payment->charge,
            ruleMin: $payment->ruleMin,
            ruleMax: $payment->ruleMax,
            visible: (int) $payment->visible,
        );
    }

    #[Override]
    public function remove(string $paymentId): void
    {
        if (! ctype_digit($paymentId)) {
            return;
        }
        $id = (int) $paymentId;
        $this->db->tpayment_option_delete(id: $id);
        $this->db->tpayment_delete(id: $id);
    }

    #[Override]
    public function reorder(string $paymentId, int $sortNo): void
    {
        if (ctype_digit($paymentId)) {
            $this->db->tpayment_reorder(id: (int) $paymentId, sortNo: $sortNo);
        }
    }

    #[Override]
    public function setVisible(string $paymentId, bool $visible): void
    {
        if (ctype_digit($paymentId)) {
            $this->db->tpayment_visible(id: (int) $paymentId, visible: (int) $visible);
        }
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): PaymentMethodAdminEntity
    {
        return new PaymentMethodAdminEntity(
            paymentId: (string) (int) $row['id'],
            paymentMethodName: (string) ($row['payment_method'] ?? ''),
            charge: (int) $row['charge'],
            ruleMin: $row['rule_min'] === null ? null : (int) $row['rule_min'],
            ruleMax: $row['rule_max'] === null ? null : (int) $row['rule_max'],
            visible: (bool) $row['visible'],
        );
    }
}
