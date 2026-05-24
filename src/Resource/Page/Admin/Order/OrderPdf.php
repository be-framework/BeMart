<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Order;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Form\AdminOrderPdfForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE 帳票出力 — Order Tier-2 (`admin/Order/order_pdf.twig`).
 *
 *   GET /admin/order/order-pdf?orderNo=…
 *
 * The delivery-note (納品書) PDF options screen: the admin sets a
 * title / greeting / note / print date, then submits to the PDF
 * exporter. The actual PDF generation lives at the sibling
 * {@see ExportOrderPdf} resource (a Phase 2 stub that streams
 * `application/pdf`); this resource is the options FORM only.
 *
 * AUTHZ is a direct admin-session check (Pattern B — this GET serves
 * the form shell, no Be transition is invoked); a non-admin firewall
 * is refused with 403. The options form renders blank so the page is
 * faithful with empty JSON-backed fake storage.
 */
class OrderPdf extends ResourceObject
{
    public function __construct(
        private readonly AdminSessionInterface $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * @psalm-taint-source input $orderNo
     */
    #[Link(rel: 'goExportOrderPdf', href: 'page://self/admin/order/export-order-pdf', method: 'get')]
    #[Link(rel: 'goOrderList', href: 'page://self/admin/order-list')]
    public function onGet(string $orderNo = ''): static
    {
        if ($this->adminSession->adminId() === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminOrderPdfForm::class);
        assert($form instanceof AdminOrderPdfForm);
        $form->fillValues([
            'title' => '納品書',
            'message1' => 'お買い上げありがとうございます。',
            'message2' => '下記の内容にて納品させていただきます。',
            'message3' => 'ご確認くださいますようお願いいたします。',
            'note1' => '', 'note2' => '', 'note3' => '',
            'issue_date' => '',
        ]);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'orderNo' => $orderNo,
        ];

        return $this;
    }
}
