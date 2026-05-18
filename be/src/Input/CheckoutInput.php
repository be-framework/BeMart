<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Being\CheckoutPrepared;

/**
 * Input for doCheckout — confirm the pre-order and commit it as a real order.
 *
 * Multi-stage Cascade with Multi-Reason convergence at each stage
 * (loan-application pattern):
 *
 *   CheckoutInput
 *     → CheckoutPrepared   (Stage 1 — existence + totals applied)
 *         Reasons: OrderQueryInterface, PurchaseFlowInterface
 *     → CheckoutSettled    (Stage 2 — inventory + payment + order-number)
 *         Reasons: InventoryAllocatorInterface, PaymentGatewayInterface,
 *                  OrderNumberGeneratorInterface
 *     → CheckoutCompleted  (Final — persist + mail + cart-clear)
 *         Reasons: OrderCommandInterface, MailerInterface,
 *                  CartCommandInterface
 *
 * Existence of the terminal CheckoutCompleted proves the order has been
 * written to dtb_order (orderStatus=NEW), the confirmation email has been
 * queued, and the source Cart has been cleared. Any failure along the way
 * throws a DomainException; the Resource layer maps the exception to
 * ShoppingError (HTTP 422 / 404).
 *
 * Pilot 5 intentionally does NOT model the Branching Final
 * (`CheckoutCompleted | CheckoutFailed`) — Pilot 3 already validated
 * Branching mechanics with OrderConfirmed/OrderConfirmFailed. Repeating that
 * pattern would not exercise new Be Framework surface area, so the failure
 * path stays exception-based.
 *
 * The payment method is NOT accepted from the client. It is sourced from the
 * persisted OrderEntity (which was set during the earlier doProceedToConfirm
 * step) inside CheckoutSettled. Accepting it from the client would allow
 * mass-assignment tampering — a client could substitute a different (cheaper
 * or unauthenticated) payment method id at confirm-time.
 */
#[Be(CheckoutPrepared::class)]
final readonly class CheckoutInput
{
    public function __construct(
        public string $preOrderId,
    ) {
    }
}
