<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\PaymentGatewayInterface;
use MyVendor\BeMart\Be\Exception\PaymentDeclinedException;
use Override;

use function sprintf;

/**
 * Phase 1 fake of PaymentMethod::checkout().
 *
 * Convention:
 *   - paymentMethodId === 9 → declined (used to exercise the failure path).
 *   - any other id → accepted (recorded for test assertions).
 *
 * The fake records every successful checkout so tests can prove the gateway
 * was hit exactly once even when several Reasons run in sequence.
 */
final class FakePaymentGateway implements PaymentGatewayInterface
{
    /** @var list<array{preOrderId: string, paymentMethodId: int, amount: int}> */
    private array $captures = [];

    #[Override]
    public function checkout(string $preOrderId, int $paymentMethodId, int $amount): void
    {
        if ($paymentMethodId === 9) {
            throw new PaymentDeclinedException(sprintf(
                'Payment declined for preOrderId=%s (method=%d, amount=%d).',
                $preOrderId,
                $paymentMethodId,
                $amount,
            ));
        }

        $this->captures[] = [
            'preOrderId' => $preOrderId,
            'paymentMethodId' => $paymentMethodId,
            'amount' => $amount,
        ];
    }

    /** @return list<array{preOrderId: string, paymentMethodId: int, amount: int}> */
    public function captures(): array
    {
        return $this->captures;
    }
}
