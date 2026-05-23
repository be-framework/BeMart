<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\PaymentMethodFactoryInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentMethodInterface;
use Override;
use RuntimeException;

use function sprintf;

/**
 * Pilot 3 fake — dispatches by paymentMethodId.
 *
 *   1 = 代金引換   → FakeCashOnDelivery (verify always succeeds)
 *   2 = クレジット → FakeCreditCard    (verify succeeds)
 *   9 = 検証失敗   → FakeVerifyFailing (verify returns errors)
 *
 * Method id 9 is the trigger for the branching path; orders.json points
 * the "noisy-fail" pre-order at it.
 */
final class FakePaymentMethodFactory implements PaymentMethodFactoryInterface
{
    #[Override]
    public function methodFor(int $paymentMethodId): PaymentMethodInterface
    {
        return match ($paymentMethodId) {
            1 => new FakeCashOnDelivery(),
            2 => new FakeCreditCard(),
            9 => new FakeVerifyFailing(),
            default => throw new RuntimeException(
                sprintf('Unknown paymentMethodId for fake: %d', $paymentMethodId),
            ),
        };
    }

    /**
     * Pilot (goShopping): the user-selectable methods. Id 9 (verify-failing)
     * stays excluded; it is a test-only fault-injection method.
     *
     * @return list<array{paymentMethodId: int, paymentMethodName: string}>
     */
    #[Override]
    public function available(): array
    {
        return [
            ['paymentMethodId' => 1, 'paymentMethodName' => '代金引換'],
            ['paymentMethodId' => 2, 'paymentMethodName' => 'クレジットカード'],
        ];
    }
}
