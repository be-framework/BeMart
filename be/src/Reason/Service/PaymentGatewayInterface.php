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
    /**
     * Phase B Slice 9: the production implementation will issue an HTTP /
     * RPC call to a third-party payment processor. `$preOrderId` becomes
     * a reference id on the gateway side, `$paymentMethodId` selects the
     * adapter, `$amount` is the charge. None of the three may be
     * client-controlled without prior validation — Pilot 5 F-2 fix already
     * forbids client-supplied `$paymentMethodId` (sourced from the
     * persisted order), but the explicit sink markers keep Psalm's flow
     * graph honest if a future caller forgets.
     *
     * 'network' is a custom taint type used in this codebase to mean
     * "data leaving the application boundary to an external service".
     *
     * @psalm-taint-sink network $preOrderId
     * @psalm-taint-sink network $paymentMethodId
     * @psalm-taint-sink network $amount
     */
    public function checkout(string $preOrderId, int $paymentMethodId, int $amount): void;
}
