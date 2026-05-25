<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminOrderPdfExported;

/**
 * Input for goExportOrderPdf — admin exports a delivery slip / receipt
 * PDF for one finalized order (Wave 9η, **Phase 2 stub**).
 *
 *   AdminExportOrderPdfInput → AdminOrderPdfExported (Direct, safe read)
 *
 * ALPS doc: "選択受注の納品書・領収書PDFを出力する。テンプレートは設定で
 * 変更可能。" PDF generation requires a layout engine (EC-CUBE uses
 * TCPDF + a configurable template) which is well out of Phase 1
 * scope. The Wave 9η iteration returns a text/plain placeholder body
 * keyed by the targeted orderNo so the AUTHZ + URL surface can be
 * exercised; the real PDF generator is Phase 2.
 */
#[Be(AdminOrderPdfExported::class)]
final readonly class AdminExportOrderPdfInput
{
    /**
     * @psalm-taint-source input $orderNo
     */
    public function __construct(
        public string $orderNo,
    ) {
    }
}
