<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PaymentVerification;
use MyVendor\BeMart\Be\Reason\Fake\FakeJson;
use MyVendor\BeMart\Be\Reason\Service\PaymentMethodFactoryInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentMethodInterface;
use Override;
use RuntimeException;

use function sprintf;

/**
 * Pilot 3 fake — dispatches by paymentMethodId.
 *
 *   1 = 代金引換   → verify succeeds
 *   2 = クレジット → verify succeeds
 *   9 = 検証失敗   → verify returns errors
 *
 * Method id 9 is the trigger for the branching path; orders.json points
 * the "noisy-fail" pre-order at it.
 */
final class FakePaymentMethodFactory implements PaymentMethodFactoryInterface
{
    #[Override]
    public function methodFor(int $paymentMethodId): PaymentMethodInterface
    {
        foreach (FakeJson::rows('payment_methods.json') as $row) {
            if ((int) $row['paymentMethodId'] !== $paymentMethodId) {
                continue;
            }

            return match ((string) $row['fakeHandler']) {
                'cash_on_delivery', 'credit_card' => self::paymentMethod(success: true),
                'verify_failing' => self::paymentMethod(
                    success: false,
                    errors: ['Card validation failed'],
                ),
                default => throw new RuntimeException(sprintf(
                    'Unknown fake payment handler: %s',
                    (string) $row['fakeHandler'],
                )),
            };
        }

        throw new RuntimeException(sprintf('Unknown paymentMethodId for fake: %d', $paymentMethodId));
    }

    /**
     * @param list<string> $errors
     */
    private static function paymentMethod(bool $success, array $errors = []): PaymentMethodInterface
    {
        return new readonly class ($success, $errors) implements PaymentMethodInterface {
            /** @param list<string> $errors */
            public function __construct(
                private bool $success,
                private array $errors,
            ) {
            }

            #[Override]
            public function verify(OrderEntity $preOrder): PaymentVerification
            {
                return new PaymentVerification(
                    success: $this->success,
                    errors: $this->errors,
                );
            }
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
        $rows = [];
        foreach (FakeJson::rows('payment_methods.json') as $row) {
            if (! (bool) ($row['available'] ?? false)) {
                continue;
            }

            $rows[] = [
                'paymentMethodId' => (int) $row['paymentMethodId'],
                'paymentMethodName' => (string) $row['paymentMethodName'],
            ];
        }

        return $rows;
    }
}
