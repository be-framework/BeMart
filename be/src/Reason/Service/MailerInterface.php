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
}
