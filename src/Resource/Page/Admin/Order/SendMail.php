<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Order;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminOrderMailSent;
use MyVendor\BeMart\Be\Input\AdminSendOrderMailInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doSendOrderMail — 受注メールを送信する (Wave 9η).
 *
 *   POST /admin/order/send-mail
 *
 * Reuses {@see \MyVendor\BeMart\Be\Reason\Service\MailerInterface::sendOrderConfirmation}
 * (Pilot 5) — the same call that fires after a customer-driven
 * checkout. The custom subject / body overrides ALPS surfaces on
 * `doSendOrderMail.descriptor` are not wired in Wave 9η (the Mailer
 * interface only takes the order entity); Phase 2 will extend the
 * Mailer contract.
 *
 * Failure mapping:
 *   - Invalid CSRF                          → 403
 *   - SemanticVariableException             → 400 (orderNo format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *   - OrderNotFoundException                → 404
 */
class SendMail extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $orderNo
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goOrder', href: 'page://self/admin/order', method: 'get')]
    public function onPost(
        string $orderNo,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new AdminSendOrderMailInput(orderNo: $orderNo));
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

        assert($final instanceof AdminOrderMailSent);

        $this->code = Code::OK;
        $this->body = [
            'orderNo' => $final->orderNo,
            'customerId' => $final->customerId,
            'message' => '注文確認メールを再送しました。',
        ];

        return $this;
    }
}
