<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ShippingAddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\MailerInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Shipping notification mail sent — Final, proof an admin dispatched
 * the "your order has shipped" mail for an order
 * (`doSendShippingNotifyMail`).
 *
 *   SendShippingNotifyMailInput → ShippingNotifyMailSent  (Direct, unsafe)
 *
 * AUTHZ — cross-firewall ladder (same as {@see AdminOrderMailSent}):
 *   1. No admin session   → UnauthorizedAdminAccessException  (403)
 *   2. Unknown orderNo    → OrderNotFoundException            (404)
 *
 * Reuses {@see MailerInterface::sendShippingNotification}; the order's
 * tracking number (if one was set via `doUpdateTrackingNumber`) is
 * read from {@see ShippingAddressStorageInterface} and passed through
 * so the mail body can carry the carrier handle. The Mailer contract
 * is non-throwing — a delivery failure is logged, not propagated.
 *
 * Idempotency: ALPS marks this `unsafe` (not idempotent). A replay
 * with the same orderNo fires a NEW mail every time — by design, the
 * admin is explicitly asking to "notify again".
 */
final readonly class ShippingNotifyMailSent
{
    public string $orderNo;
    public string $customerId;
    public string|null $trackingNumber;

    public function __construct(
        #[Input] string $orderNo,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] OrderQueryInterface $orderQuery,
        #[Inject] ShippingAddressStorageInterface $shippingAddresses,
        #[Inject] MailerInterface $mailer,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $order = $orderQuery->byOrderNo($orderNo);
        if ($order === null) {
            throw new OrderNotFoundException();
        }

        $trackingNumber = $shippingAddresses->trackingNumberByOrderNo($order->orderNo)->trackingNumber;
        $mailer->sendShippingNotification($order, $trackingNumber);

        $this->orderNo = $order->orderNo;
        $this->customerId = $order->customerId;
        $this->trackingNumber = $trackingNumber;
    }
}
