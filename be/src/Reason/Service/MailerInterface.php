<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

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
    public function sendOrderConfirmation(FinalizedOrderEntity $order): void;
}
