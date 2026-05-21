<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\PaymentMethodAdminEntity;
use Override;

use function array_values;
use function ksort;

/**
 * In-memory PaymentMethodAdmin store. Starts empty — tests seed via POST.
 * Singleton so reads see same-request writes.
 */
final class FakePaymentMethodAdminStorage implements PaymentMethodAdminStorageInterface
{
    /** @var array<string, PaymentMethodAdminEntity> keyed by paymentId */
    private array $byId = [];

    /**
     * Storage-only `sort_no` per row — dtb_payment has the column but
     * the 6-field {@see PaymentMethodAdminEntity} does not project it.
     *
     * @var array<string, int>
     */
    private array $sortNo = [];

    /** @return list<PaymentMethodAdminEntity> */
    #[Override]
    public function list(): array
    {
        $rows = $this->byId;
        ksort($rows);

        return array_values($rows);
    }

    #[Override]
    public function getById(string $paymentId): PaymentMethodAdminEntity|null
    {
        return $this->byId[$paymentId] ?? null;
    }

    #[Override]
    public function put(PaymentMethodAdminEntity $payment): void
    {
        $this->byId[$payment->paymentId] = $payment;
    }

    #[Override]
    public function remove(string $paymentId): void
    {
        unset($this->byId[$paymentId], $this->sortNo[$paymentId]);
    }

    #[Override]
    public function reorder(string $paymentId, int $sortNo): void
    {
        if (! isset($this->byId[$paymentId])) {
            return;
        }

        $this->sortNo[$paymentId] = $sortNo;
    }

    #[Override]
    public function setVisible(string $paymentId, bool $visible): void
    {
        $current = $this->byId[$paymentId] ?? null;
        if ($current === null) {
            return;
        }

        // `visible` IS projected onto PaymentMethodAdminEntity —
        // rebuild the row so `list()` / `getById()` reflect the toggle.
        $this->byId[$paymentId] = new PaymentMethodAdminEntity(
            paymentId: $current->paymentId,
            paymentMethodName: $current->paymentMethodName,
            charge: $current->charge,
            ruleMin: $current->ruleMin,
            ruleMax: $current->ruleMax,
            visible: $visible,
        );
    }

    /** Test introspection: the `sort_no` last written for a row. */
    public function sortNoOf(string $paymentId): int|null
    {
        return $this->sortNo[$paymentId] ?? null;
    }
}
