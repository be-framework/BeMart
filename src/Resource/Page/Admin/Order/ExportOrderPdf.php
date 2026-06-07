<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Order;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminOrderPdfExported;
use MyVendor\BeMart\Be\Input\AdminExportOrderPdfInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;
use function explode;
use function is_scalar;
use function is_string;
use function mb_convert_encoding;
use function str_contains;
use function trim;

/**
 * EC-CUBE goExportOrderPdf — 帳票PDFをエクスポートする.
 *
 *   GET /admin/order/export-order-pdf?orderNos[]=…
 *
 * The Resource only normalizes EC-CUBE's `ids[]`/legacy `orderNo`
 * request shape, then calls the Be Final. TCPDF/FPDI rendering is kept
 * behind OrderPdfCompatibilityInterface.
 *
 * Failure mapping:
 *   - SemanticVariableException             → 400 (orderNos format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *   - OrderNotFoundException                → 404 (unknown orderNo; all-or-nothing)
 */
class ExportOrderPdf extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * ALPS `goExportOrderPdf` に対応する GET 操作。
     * @param array<int, mixed>|string $orderNos
     *
     * @psalm-taint-source input $orderNos
     * @psalm-taint-source input $orderNo
     */
    #[Alps('goExportOrderPdf')]
    #[JsonSchema(schema: 'get-admin-order-export-order-pdf.json', params: 'get-admin-order-export-order-pdf.param.json')]
    #[Link(rel: 'goOrderList', href: 'page://self/admin/order-list')]
    #[Link(rel: 'goExportOrder', href: 'page://self/admin/order/export-order', method: 'get')]
    public function onGet(array|string $orderNos = [], string $orderNo = ''): static
    {
        try {
            $normalizedOrderNos = $this->normalizeOrderNos($orderNos, $orderNo);
            if ($normalizedOrderNos === []) {
                $this->code = Code::BAD_REQUEST;
                $this->body = ['message' => '注文番号リストが不正です。1〜100件の有効な注文番号を指定してください。'];

                return $this;
            }

            $final = ($this->becoming)(new AdminExportOrderPdfInput(
                orderNos: $normalizedOrderNos,
            ));
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
        $this->headers['Content-Disposition'] = $final->contentDisposition;
        $this->body = [
            'orderNo' => $final->orderNo,
            'orderNos' => $final->orderNos,
            'pdf' => mb_convert_encoding($final->pdf, 'UTF-8', 'UTF-8'),
            'size' => $final->size,
            'fileName' => $final->fileName,
            'message' => $final->message,
        ];

        return $this;
    }

    /**
     * @param array<int, mixed>|string $orderNos
     * @return list<string>
     */
    private function normalizeOrderNos(array|string $orderNos, string $orderNo): array
    {
        $rawValues = $orderNos === [] && $orderNo !== '' ? [$orderNo] : $orderNos;
        if (is_string($rawValues)) {
            $rawValues = str_contains($rawValues, ',')
                ? explode(',', $rawValues)
                : [$rawValues];
        }

        $normalized = [];
        foreach ($rawValues as $rawValue) {
            if (! is_scalar($rawValue)) {
                continue;
            }

            $value = trim((string) $rawValue);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return $normalized;
    }
}
