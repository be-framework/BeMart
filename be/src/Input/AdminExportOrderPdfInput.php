<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminOrderPdfExported;

/**
 * Input for goExportOrderPdf — admin exports a delivery slip / receipt
 * PDF for one or more finalized orders.
 *
 *   AdminExportOrderPdfInput → AdminOrderPdfExported (Direct, safe read)
 *
 * ALPS doc: "選択受注の納品書・領収書PDFを出力する。テンプレートは設定で
 * 変更可能。" The Be layer delegates the EC-CUBE/TCPDF-compatible
 * rendering to OrderPdfCompatibilityInterface so Symfony/TCPDF details
 * do not leak into Resources or Be domain code.
 */
#[Be(AdminOrderPdfExported::class)]
final readonly class AdminExportOrderPdfInput
{
    /**
     * @param list<string> $orderNos
     *
     * @psalm-taint-source input $orderNos
     */
    public function __construct(
        public array $orderNos,
    ) {
    }
}
