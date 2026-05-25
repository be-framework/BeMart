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

    /**
     * Enumerate the user-selectable payment methods for the checkout review
     * page (goShopping). The verify-failing fixture (id=9) is intentionally
     * excluded — it exists solely as a fault-injection harness for the
     * doConfirmOrder branching path, not as a user-facing choice. Production
     * binding would consult the plugin registry filtered by the customer's
     * eligibility (e.g. region, sale type).
     *
     * @return list<array{paymentMethodId: int, paymentMethodName: string}>
     */
    public function available(): array;
}
