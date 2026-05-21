<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ActivationMailResent;

/**
 * Input for `doResendActivationMail` — an admin resends the
 * email-verification (full-registration) mail to a 仮会員 (provisional
 * customer) from the admin customer list (Phase 3 ALPS-audit
 * remediation).
 *
 *   ResendActivationMailInput → ActivationMailResent
 *                                 (Direct, unsafe, admin AUTHZ)
 *
 * Derived from EC-CUBE's `admin_customer_resend` route. The mail carries
 * an activation link embedding the customer's `secretKey`; the customer
 * later promotes to a full member via `doActivateCustomer`. ALPS marks
 * it `unsafe` — each call sends a fresh mail, by design.
 *
 * The descriptor in `alps.json` carries `descriptor: [{"href":
 * "#email"}]`: the admin picks the target customer by email from the
 * customer-list row, so `email` is the body field. `email` matches the
 * {@see \MyVendor\BeMart\Be\Semantic\Email} validator.
 */
#[Be(ActivationMailResent::class)]
final readonly class ResendActivationMailInput
{
    /**
     * @psalm-taint-source input $email
     */
    public function __construct(
        public string $email,
    ) {
    }
}
