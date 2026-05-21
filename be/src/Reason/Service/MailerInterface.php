<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Entity\ContactEntity;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;

/**
 * Sends the order-confirmation email after checkout succeeds.
 *
 * EC-CUBE's PurchaseFlow does this via MailService::sendOrderMail() once
 * checkout() returns. Failure here is intentionally non-blocking in the
 * EC-CUBE flow (the order is already taken), so the implementation must
 * NOT throw — it logs and returns. The Pilot 5 fake records sent calls so
 * tests can assert "mail was attempted exactly once".
 */
interface MailerInterface
{
    /**
     * Phase B Slice 9: the order entity gets rendered into the mail body
     * (subject line, greeting, line items). All string fields end up in
     * HTML / plain-text output and must be escaped at render time. Marked
     * as an html taint sink so any unsanitized user-controlled string
     * reaching this method surfaces in `composer psalm-taint`.
     *
     * @psalm-taint-sink html $order
     */
    public function sendOrderConfirmation(FinalizedOrderEntity $order): void;

    /**
     * Pilot 15 doSubmitContact: send the inquiry to the shop and an
     * auto-reply to the submitter. EC-CUBE's MailService::sendContactMail
     * also targets two recipients. The contact body is highly user-
     * controlled (free-text), so it's an html taint sink.
     *
     * @psalm-taint-sink html $contact
     */
    public function sendContactInquiry(ContactEntity $contact): void;

    /**
     * Pilot 14 doRequestPasswordReset: dispatch the reset link.
     * The resetKey itself originates from a CSPRNG (not user input),
     * so it is not a sink concern. The email is the recipient.
     *
     * @psalm-taint-sink html $email
     */
    public function sendPasswordReset(string $email, string $resetKey): void;

    /**
     * Pilot doWithdrawCustomer: send the "your account has been
     * withdrawn" confirmation. The Final captures the ORIGINAL email
     * (before it is overwritten with the dummy placeholder) and passes
     * it here, so the goodbye message reaches the human who still owns
     * the address. The name fields go into the greeting.
     *
     * @psalm-taint-sink html $email
     */
    public function sendWithdrawConfirmation(string $email, string $name01, string $name02): void;

    /**
     * `doSendShippingNotifyMail`: dispatch the shipping-notification
     * mail for a finalized order. EC-CUBE's
     * `admin_shipping_notify_mail` route lets an admin tell the customer
     * "your order has shipped". Distinct from
     * {@see sendOrderConfirmation} — that is the order-received mail;
     * this one is the shipped notification, sent against an order whose
     * shipment is on its way.
     *
     * ALPS marks the transition `unsafe`: each call sends a fresh mail.
     * Like the rest of the Mailer contract this method MUST NOT throw —
     * delivery failure is logged, not propagated. The optional
     * `$trackingNumber` is rendered into the body when present so the
     * customer gets the carrier tracking handle.
     *
     * @psalm-taint-sink html $order
     */
    public function sendShippingNotification(FinalizedOrderEntity $order, string|null $trackingNumber): void;
}
