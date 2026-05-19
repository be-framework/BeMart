<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminOrderMailSent;

/**
 * Input for doSendOrderMail — admin manually resends the order
 * confirmation email for an existing finalized order.
 *
 *   AdminSendOrderMailInput → AdminOrderMailSent  (Direct, unsafe)
 *
 * ALPS `doSendOrderMail.type=unsafe`. EC-CUBE renders the saved order
 * with the chosen MailTemplate via MailService; this Wave reuses
 * {@see \MyVendor\BeMart\Be\Reason\Service\MailerInterface::sendOrderConfirmation}
 * (Pilot 5) so the call records a "mail was attempted" event without
 * touching the order's persisted row.
 *
 * Note on Phase 2: ALPS surfaces `mailSubject` + `mailBody` as ad-hoc
 * overrides on top of the template; the present iteration ignores any
 * custom subject/body (the Mailer interface only takes the order
 * entity). Surfacing the override knobs is deferred — when the Mailer
 * grows a per-call subject/body API.
 */
#[Be(AdminOrderMailSent::class)]
final readonly class AdminSendOrderMailInput
{
    /**
     * @psalm-taint-source input $orderNo
     */
    public function __construct(
        public string $orderNo,
    ) {
    }
}
