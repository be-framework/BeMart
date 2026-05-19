<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ShoppingFetched;

/**
 * Input for goShopping — render the checkout review page that the
 * customer sees BEFORE submitting doCheckout (Pilot 5).
 *
 * Direct pattern: Input → Final. The Final injects SessionInterface
 * (AUTHN), CartQueryInterface, CustomerQueryInterface, and
 * PaymentMethodFactoryInterface and assembles the review-page
 * projection (current carts + default shipping address + available
 * payment methods + canCheckout flag).
 *
 * AUTHN:
 *   In EC-CUBE, goShopping is reachable for anonymous (guest checkout)
 *   sessions. However, our Pilot 5 doCheckout currently REQUIRES a
 *   session for AUTHZ; to keep the two consistent we require the
 *   session here too. The Final raises UnauthenticatedException on a
 *   null session, which the Resource maps to 401. Guest-checkout
 *   support is Phase 2 and would extend the session model.
 *
 * The `sessionPrefix` is the cart-partition key (same convention as
 * goCart / doAddCartItem). Default matches Pilot 9 fixtures so the
 * test harness sees a populated cart on startup.
 */
#[Be(ShoppingFetched::class)]
final readonly class GetShoppingInput
{
    /**
     * @psalm-taint-source input $sessionPrefix
     */
    public function __construct(
        public string $sessionPrefix = 'session-prefix-1',
    ) {
    }
}
