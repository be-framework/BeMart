<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Order;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;

/**
 * EC-CUBE 受注メール確認 — Order Tier-2 (`admin/Order/mail_confirm.twig`).
 *
 *   GET /admin/order/mail-confirm?orderNo=…
 *
 * The confirmation step shown between the mail composer
 * ({@see SendMail}) and the actual send: a read-only preview of the
 * subject / body the admin is about to send. EC-CUBE renders the
 * composed mail content here; the Be domain re-sends the
 * order-confirmation mail keyed by orderNo only
 * ({@see \MyVendor\BeMart\Be\Input\AdminSendOrderMailInput}), so this
 * page carries the orderNo through to the send action and renders the
 * confirm-and-send shell.
 *
 * AUTHZ is a direct admin-session check (Pattern B — this is a
 * read-only preview page, no Be transition is invoked); a non-admin
 * firewall is refused with 403.
 */
class MailConfirm extends ResourceObject
{
    public function __construct(
        private readonly AdminSessionInterface $adminSession,
    ) {
    }

    /**
     * @psalm-taint-source input $orderNo
     */
    #[Link(rel: 'doSendOrderMail', href: 'page://self/admin/order/send-mail', method: 'post')]
    #[Link(rel: 'goOrderMail', href: 'page://self/admin/order/send-mail', method: 'get')]
    public function onGet(string $orderNo = ''): static
    {
        if ($this->adminSession->adminId() === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $this->code = Code::OK;
        $this->body = [
            'orderNo' => $orderNo,
        ];

        return $this;
    }
}
