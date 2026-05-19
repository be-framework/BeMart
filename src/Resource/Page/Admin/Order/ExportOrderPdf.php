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
use MyVendor\BeMart\Be\Final\AdminOrderPdfExported;
use MyVendor\BeMart\Be\Input\AdminExportOrderPdfInput;

use function assert;

/**
 * EC-CUBE goExportOrderPdf — 帳票PDFをエクスポートする (Wave 9η,
 * **Phase 2 stub**).
 *
 *   GET /admin/order/export-order-pdf?orderNo=…
 *
 * Real PDF generation (EC-CUBE: TCPDF + a configurable layout) is
 * Phase 2 scope. The stub returns a text/plain placeholder body keyed
 * by the targeted orderNo so the AUTHZ + URL surface can be exercised
 * in isolation. Despite the stubbed body, the response surfaces
 * `Content-Type: application/pdf` so downstream clients can wire the
 * download affordance ahead of the real renderer.
 *
 * Failure mapping:
 *   - SemanticVariableException             → 400 (orderNo format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *   - OrderNotFoundException                → 404
 */
class ExportOrderPdf extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * @psalm-taint-source input $orderNo
     */
    #[Link(rel: 'goOrderList', href: 'page://self/admin/order-list')]
    public function onGet(string $orderNo): static
    {
        try {
            $final = ($this->becoming)(new AdminExportOrderPdfInput(orderNo: $orderNo));
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

        assert($final instanceof AdminOrderPdfExported);

        $this->code = Code::OK;
        $this->headers['Content-Type'] = 'application/pdf';
        $this->body = [
            'orderNo' => $final->orderNo,
            'pdf' => $final->pdf,
            'size' => $final->size,
            'message' => $final->message,
        ];

        return $this;
    }
}
