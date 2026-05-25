<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ShippingNotifyMailSent;

/**
 * Input for `doSendShippingNotifyMail` — admin sends the shipping
 * notification mail for an order (Phase 3 ALPS-audit remediation).
 *
 *   SendShippingNotifyMailInput → ShippingNotifyMailSent
 *                                  (Direct, unsafe, admin AUTHZ)
 *
 * Derived from EC-CUBE's `admin_shipping_notify_mail` route. Distinct
 * from `doSendOrderMail` (the order-received mail): this is the
 * "your order has shipped" notification. ALPS marks it `unsafe` — each
 * call sends a fresh mail, by design.
 *
 * `orderNo` matches the {@see \MyVendor\BeMart\Be\Semantic\OrderNo}
 * validator.
 */
#[Be(ShippingNotifyMailSent::class)]
final readonly class SendShippingNotifyMailInput
{
    /**
     * @psalm-taint-source input $orderNo
     */
    public function __construct(
        public string $orderNo,
    ) {
    }
}
