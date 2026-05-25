<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function sprintf;
use function strlen;

/**
 * Admin order PDF exported — Final, **Phase 2 stub** (Wave 9η).
 *
 *   AdminExportOrderPdfInput → AdminOrderPdfExported (Direct, safe read)
 *
 * AUTHZ — cross-firewall:
 *   1. No admin session → UnauthorizedAdminAccessException (403)
 *   2. Unknown orderNo  → OrderNotFoundException          (404)
 *
 * Real PDF generation (EC-CUBE: TCPDF + a configurable layout) is
 * Phase 2 scope. This stub returns a text/plain placeholder body
 * keyed by the targeted orderNo so the AUTHZ + URL surface can be
 * exercised in isolation. The Resource layer surfaces
 * `Content-Type: application/pdf` so downstream clients can wire the
 * download affordance ahead of the real renderer.
 */
final readonly class AdminOrderPdfExported
{
    public string $orderNo;
    public string $pdf;
    public int $size;
    public string $message;

    public function __construct(
        #[Input] string $orderNo,
        #[Inject] AdminSession $adminSession,
        #[Inject] OrderQueryInterface $orderQuery,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $order = $orderQuery->byOrderNo($orderNo);
        if ($order === null) {
            throw new OrderNotFoundException();
        }

        $body = sprintf(
            "PDF STUB\norderNo: %s\ncustomerId: %s\ntotal: %d\norderDate: %s\n",
            $order->orderNo,
            $order->customerId,
            $order->total,
            $order->orderDate,
        );

        $this->orderNo = $order->orderNo;
        $this->pdf = $body;
        $this->size = strlen($body);
        $this->message = 'PDF export is a Phase 2 stub — the body is a text placeholder.';
    }
}
