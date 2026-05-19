<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CustomerWithdrawn;

/**
 * Input for doWithdrawCustomer — the logged-in customer self-cancels
 * their account.
 *
 * Direct pattern: Input → Final.
 *
 *   WithdrawCustomerInput → CustomerWithdrawn (Final)
 *
 * AUTHZ via Session — customerId is NOT in the body (Pilot 5 F-2 +
 * Pilot 8 lesson). The Final pulls it from SessionInterface and
 * refuses to proceed when the session is anonymous. A logged-in
 * customer cannot withdraw a different customer's account by
 * tampering with form fields; the body simply doesn't accept an id.
 *
 * `sessionPrefix` scopes the cart-clear side-effect (cartKey is
 * `{sessionPrefix}_{saleTypeId}`). Default matches AddCartItemInput
 * so resource callers and tests need not thread it through.
 *
 * Session itself is wiped by the EC-CUBE-side EventListener after
 * this transition (Slice 7.2 contract — same as Pilot 6 doLogin /
 * doLogout). The Be layer's responsibility ends with the four durable
 * effects described in CustomerWithdrawn.
 *
 * @link https://schema.org/DeleteAction
 */
#[Be(CustomerWithdrawn::class)]
final readonly class WithdrawCustomerInput
{
    /**
     * @psalm-taint-source input $sessionPrefix
     */
    public function __construct(
        public string $sessionPrefix = 'session-prefix-1',
    ) {
    }
}
