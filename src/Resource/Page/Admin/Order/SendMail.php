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
use MyVendor\BeMart\Be\Final\AdminOrderMailSent;
use MyVendor\BeMart\Be\Input\AdminSendOrderMailInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminOrderMailForm;
use Ray\WebFormModule\FormFactory;

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
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * EC-CUBE 受注メール送信 — Order Tier-2.
     *
     * Thin GET renderer for `admin/Order/mail.twig` — the order-mail
     * composition screen. The POST below re-sends the confirmation
     * mail; this GET serves the composition form keyed by the order.
     * AUTHZ is a direct admin-session check (Pattern B — no Be
     * transition is invoked on the GET path). The composition fields
     * render blank so the page is faithful with empty JSON-backed fake storage.
     *
     * @psalm-taint-source input $orderNo
     */
    #[Link(rel: 'doSendOrderMail', href: 'page://self/admin/order/send-mail', method: 'post')]
    #[Link(rel: 'goOrderMailConfirm', href: 'page://self/admin/order/mail-confirm', method: 'get')]
    #[Link(rel: 'goOrder', href: 'page://self/admin/order', method: 'get')]
    public function onGet(string $orderNo = ''): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminOrderMailForm::class);
        assert($form instanceof AdminOrderMailForm);
        $form->fillValues([
            'template' => '', 'mail_subject' => '', 'mail_header' => '', 'mail_footer' => '',
        ]);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'orderNo' => $orderNo,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $orderNo
     */
    #[Link(rel: 'goOrder', href: 'page://self/admin/order', method: 'get')]
    #[Link(rel: 'goExportOrderPdf', href: 'page://self/admin/order/export-order-pdf', method: 'get')]
    #[CsrfProtected]
    public function onPost(
        string $orderNo,
    ): static {
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
            'sendDate' => $final->sendDate,
            'mailSubject' => $final->mailSubject,
            'mailBody' => $final->mailBody,
            'message' => '注文確認メールを再送しました。',
        ];

        return $this;
    }
}
