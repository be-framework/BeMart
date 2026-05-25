<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminCustomerDeleted;

/**
 * Input for doDeleteCustomer — an admin soft-deletes a customer
 * (management screen).
 *
 * Direct pattern (hello-world demo): Input → Final, no intermediate
 * Being. The Final injects AdminSession to refuse non-admin
 * requests (Wave 4 cross-firewall AUTHZ) and then performs the
 * EC-CUBE soft-delete:
 *
 *   AdminDeleteCustomerInput → AdminCustomerDeleted (Final)
 *
 * AUTHZ design — different shape from Pilot doWithdrawCustomer (Wave 2G):
 *
 *   - Wave 2G {@see WithdrawCustomerInput} pulls customerId from
 *     CustomerSession (customer self-withdraws, ANY customerId in the
 *     body is refused via mass-assignment safety — F-2 lesson).
 *   - This admin variant pulls the TARGET customerId from the request
 *     body (the admin picks which customer to delete) AND pulls the
 *     authorising adminId from AdminSession. The Be Framework
 *     G-17 rule (Pilot 10) forbids reusing CustomerWithdrawn for this
 *     differently-shaped chain — different intent ⇒ different Final.
 *
 * The descriptor in alps.json carries `descriptor: [{"href": "#email"}]`
 * but the doc text ("会員を物理削除する。受注は会員IDをNULLにして保持。")
 * makes the durable identifier customerId, not email — emails get
 * replaced with a dummy on delete and would not survive a re-delete
 * lookup. We surface customerId as the body field; the admin UI maps
 * the customer-list row's customerId in.
 *
 * @link https://schema.org/DeleteAction
 */
#[Be(AdminCustomerDeleted::class)]
final readonly class AdminDeleteCustomerInput
{
    /**
     * Wave 6: the target customerId is user-controlled input from the
     * admin UI (admin clicks a customer-list row, which feeds the
     * opaque customerId into this form). Marked as a taint source so
     * any flow that reaches a sensitive sink surfaces in
     * `composer psalm-taint`.
     *
     * @psalm-taint-source input $customerId
     */
    public function __construct(
        public string $customerId,
    ) {
    }
}
