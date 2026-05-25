<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\CustomerAlreadyActivatedException;
use MyVendor\BeMart\Be\Exception\CustomerNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\MailerInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Activation mail resent — Final, proof an admin re-dispatched the
 * email-verification (full-registration) mail for a provisional
 * customer (`doResendActivationMail`).
 *
 *   ResendActivationMailInput → ActivationMailResent  (Direct, unsafe)
 *
 * AUTHZ — cross-firewall ladder (same shape as {@see ShippingNotifyMailSent}
 * and {@see AdminCustomerDeleted}):
 *   1. No admin session       → UnauthorizedAdminAccessException     (403)
 *   2. Unknown email          → CustomerNotFoundException            (404)
 *   3. Customer NOT provisional → CustomerAlreadyActivatedException   (409)
 *
 * Sequencing matters: AUTHZ-cross-firewall is checked FIRST, then
 * existence, then state. An admin-anonymous client learns NOTHING about
 * which emails resolve — same anti-enumeration discipline as goCustomer.
 *
 * Step 3 rejects resending to a non-provisional member: only a 仮会員
 * (customerStatus = 1) is mid-activation. An active customer
 * (customerStatus = 2) already consumed their `secretKey` via
 * `doActivateCustomer`, so there is no activation link to send.
 * Treating it as a meaningful 409 (rather than a silent success) tells
 * the admin the row they clicked is no longer a 仮会員.
 *
 * Reuses {@see MailerInterface::sendCustomerActivation}; the customer's
 * `secretKey` (the email-verification token EC-CUBE embeds in the
 * activation URL) is read from {@see CustomerEntity} and passed through.
 * The Mailer contract is non-throwing — a delivery failure is logged,
 * not propagated.
 *
 * Idempotency: ALPS marks this `unsafe` (not idempotent). A replay with
 * the same email fires a NEW mail every time — by design, the admin is
 * explicitly asking to "resend".
 *
 * The Final's public surface is intentionally minimal: customerId and
 * email. The `secretKey` is NOT echoed — it is a one-time token that
 * belongs only in the mail body.
 */
final readonly class ActivationMailResent
{
    public string $customerId;
    public string $email;

    public function __construct(
        #[Input] string $email,
        #[Inject] AdminSession $adminSession,
        #[Inject] CustomerQueryInterface $customerQuery,
        #[Inject] MailerInterface $mailer,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $customer = $customerQuery->byEmail($email);
        if ($customer === null) {
            throw new CustomerNotFoundException();
        }

        // Only a 仮会員 (customerStatus = 1) is mid-activation. An active
        // member has already consumed their secretKey, so there is no
        // activation link left to resend — the `secretKey === null`
        // guard also satisfies the Mailer's non-null contract below.
        if ($customer->customerStatus !== 1 || $customer->secretKey === null) {
            throw new CustomerAlreadyActivatedException();
        }

        $mailer->sendCustomerActivation($customer->email, $customer->secretKey);

        $this->customerId = $customer->customerId;
        $this->email = $customer->email;
    }
}
