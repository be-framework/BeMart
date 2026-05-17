<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Resolves a PaymentMethod implementation by paymentMethodId.
 *
 * Mirrors EC-CUBE's ShoppingController::createPaymentMethod() which dispatches
 * by Order::getPayment()::getMethodClass(). Pilot 3 has a static fake mapping;
 * production binding would use the plugin registry.
 */
interface PaymentMethodFactoryInterface
{
    public function methodFor(int $paymentMethodId): PaymentMethodInterface;
}
