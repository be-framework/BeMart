<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use DateTimeImmutable;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryMailEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderMailHistoryCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\MailerInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin order mail sent — Final, proof an admin manually re-issued
 * the order confirmation mail for a finalized order.
 *
 *   AdminSendOrderMailInput → AdminOrderMailSent  (Direct, unsafe)
 *
 * AUTHZ — cross-firewall (same ladder as the rest of Wave 7+ admin
 * Finals):
 *
 *   1. No admin session     → UnauthorizedAdminAccessException  (403)
 *   2. Unknown orderNo      → OrderNotFoundException            (404)
 *
 * The Final reuses {@see MailerInterface::sendOrderConfirmation} (the
 * same call Pilot 5 fires after a customer-driven checkout). The fake
 * mailer captures each call, so tests assert "mail was attempted
 * exactly once" with the right order entity. The Mailer contract is
 * non-throwing — failure here is logged not propagated, matching the
 * EC-CUBE PurchaseFlow convention.
 *
 * Idempotency: ALPS marks this `unsafe` (not idempotent). A replay
 * with the same orderNo fires a NEW mail every time — by design, the
 * admin is explicitly asking for "send it again".
 */
final readonly class AdminOrderMailSent
{
    public string $orderNo;
    public string $customerId;
    public string $sendDate;
    public string $mailSubject;
    public string $mailBody;

    public function __construct(
        #[Input] string $orderNo,
        #[Inject] AdminSession $adminSession,
        #[Inject] OrderQueryInterface $orderQuery,
        #[Inject] OrderMailHistoryCommandInterface $mailHistoryCommand,
        #[Inject] MailerInterface $mailer,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $order = $orderQuery->byOrderNo($orderNo);
        if ($order === null) {
            throw new OrderNotFoundException();
        }

        $mailer->sendOrderConfirmation($order);

        $mailHistory = new OrderHistoryMailEntity(
            sendDate: (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            mailSubject: '注文確認メール',
            mailBody: '注文番号 ' . $order->orderNo . ' の確認メールを送信しました。',
        );
        $mailHistoryCommand->insert($order->orderNo, $mailHistory);

        $this->orderNo = $order->orderNo;
        $this->customerId = $order->customerId;
        $this->sendDate = $mailHistory->sendDate;
        $this->mailSubject = $mailHistory->mailSubject;
        $this->mailBody = $mailHistory->mailBody;
    }
}
