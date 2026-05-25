<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Order;

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
    ) {
    }

    /**
     * @psalm-taint-source input $orderNo
     */
    #[Link(rel: 'goOrder', href: 'page://self/admin/order', method: 'get')]
    #[CsrfProtected]
    public function onPost(
        string $orderNo,
    ): static {
        try {
            $final = ($this->becoming)(new SendShippingNotifyMailInput(orderNo: $orderNo));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (OrderNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された注文は見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof ShippingNotifyMailSent);

        $this->code = Code::OK;
        $this->body = [
            'orderNo' => $final->orderNo,
            'customerId' => $final->customerId,
            'trackingNumber' => $final->trackingNumber,
            'message' => '出荷通知メールを送信しました。',
        ];

        return $this;
    }
}
