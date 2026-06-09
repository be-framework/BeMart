<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Order;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\ShippingNotifyMailSent;
use MyVendor\BeMart\Be\Input\SendShippingNotifyMailInput;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE doSendShippingNotifyMail — 出荷通知メールを送信する (Phase 3
 * ALPS-audit remediation).
 *
 *   POST /admin/order/shipping-notify-mail
 *
 * Sends the "your order has shipped" mail for a finalized order,
 * derived from EC-CUBE's `admin_shipping_notify_mail` route. Distinct
 * from {@see SendMail} (the order-received mail). ALPS marks it
 * `unsafe` — POST is the matching verb, each call sends a fresh mail.
 *
 * Failure mapping:
 *   - Invalid CSRF                          → 403
 *   - SemanticVariableException             → 400 (orderNo format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *   - OrderNotFoundException                → 404
 */
class ShippingNotifyMail extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly AdminSession $adminSession,
        private readonly OrderQueryInterface $orders,
    ) {
    }

    /**
     * Displays the shipping-notification confirmation form.
     *
     * @psalm-taint-source input $orderNo
     */
    #[Alps('doSendShippingNotifyMail')]
    #[JsonSchema(schema: 'get-admin-order-shipping-notify-mail.json', params: 'get-admin-order-shipping-notify-mail.param.json')]
    #[Link(rel: 'doSendShippingNotifyMail', href: 'page://self/admin/order/shipping-notify-mail', method: 'post')]
    #[Link(rel: 'goOrder', href: 'page://self/admin/order', method: 'get')]
    public function onGet(string $orderNo): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $order = $this->orders->byOrderNo($orderNo);
        if ($order === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された注文は見つかりませんでした。', 'orderNo' => $orderNo];

            return $this;
        }

        $this->code = Code::OK;
        $this->body = [
            'orderNo' => $order->orderNo,
            'customerId' => $order->customerId,
            'message' => '出荷通知メールを送信します。よろしいですか？',
            'csrfToken' => null,
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/admin/order/shipping-notify-mail',
            ],
        ];

        return $this;
    }

    /**
     * ALPS `doSendShippingNotifyMail` に対応する POST 操作。
     * @psalm-taint-source input $orderNo
     */
    #[Alps('doSendShippingNotifyMail')]
    #[JsonSchema(schema: 'post-admin-order-shipping-notify-mail.json', params: 'post-admin-order-shipping-notify-mail.param.json')]
    #[Link(rel: 'goOrder', href: 'page://self/admin/order', method: 'get')]
    #[CsrfProtected]
    public function onPost(
        string $orderNo,
    ): static {
        $final = ($this->becoming)(new SendShippingNotifyMailInput(orderNo: $orderNo));

        assert($final instanceof ShippingNotifyMailSent);

        $this->code = Code::OK;
        $this->headers['Location'] = '/admin/order?orderNo=' . $final->orderNo;
        $this->body = [
            'orderNo' => $final->orderNo,
            'customerId' => $final->customerId,
            'trackingNumber' => $final->trackingNumber,
            'message' => '出荷通知メールを送信しました。',
        ];

        return $this;
    }
}
