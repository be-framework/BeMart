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
        unset($this->byId[$paymentId]);
    }
}
