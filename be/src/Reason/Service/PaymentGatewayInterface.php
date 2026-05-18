<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Confirms the payment with the payment gateway.
 *
 * Maps to EC-CUBE's PaymentMethod::checkout() step (after verify() succeeded
 * during doConfirmOrder in Pilot 3). Implementations make the actual charge
 * call. On decline the implementation throws PaymentDeclinedException and
 * the caller treats the entire checkout as failed.
 *
 * The convention in Pilot 5 is: paymentMethodId 9 simulates a decline, all
 * other ids succeed. Production binding wraps the EC-CUBE
 * PaymentMethodFactory + PaymentMethod::checkout() handshake.
 */
interface PaymentGatewayInterface
{
    public function checkout(string $preOrderId, int $paymentMethodId, int $amount): void;
}
