<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PaymentVerification;
use MyVendor\BeMart\Be\Reason\Service\PaymentMethodFactoryInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentMethodInterface;
use JsonException;
use Override;
use RuntimeException;

use function dirname;
use function file_get_contents;
use function is_array;
use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

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
        foreach (self::paymentMethodRows() as $row) {
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
        foreach (self::paymentMethodRows() as $row) {
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

    /** @return list<array<string, mixed>> */
    private static function paymentMethodRows(): array
    {
        $path = dirname(__DIR__, 4) . '/be/var/fake/payment_methods.json';
        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException(sprintf('Fake payment method fixture missing: %s', $path));
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(sprintf('Fake payment method fixture must be valid JSON: %s', $path), 0, $e);
        }
        if (! is_array($decoded)) {
            throw new RuntimeException(sprintf('Fake payment method fixture must be a JSON array: %s', $path));
        }

        $rows = [];
        foreach ($decoded as $row) {
            if (! is_array($row)) {
                throw new RuntimeException(sprintf('Fake payment method fixture rows must be objects: %s', $path));
            }

            /** @var array<string, mixed> $row */
            $rows[] = $row;
        }

        return $rows;
    }
}
