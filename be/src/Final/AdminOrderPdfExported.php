<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\OrderNosFormatException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderItemQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ShippingAddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\OrderPdfCompatibilityInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin order PDF exported — Final.
 *
 *   AdminExportOrderPdfInput → AdminOrderPdfExported (Direct, safe read)
 *
 * AUTHZ — cross-firewall:
 *   1. No admin session → UnauthorizedAdminAccessException (403)
 *   2. Unknown orderNo  → OrderNotFoundException          (404; all-or-nothing)
 *
 * The actual TCPDF/FPDI rendering is intentionally hidden behind
 * OrderPdfCompatibilityInterface. Be stays responsible for AUTHZ,
 * order existence and collecting the stable order/item/shipping snapshot.
 */
final readonly class AdminOrderPdfExported
{
    /** @var non-empty-list<string> */
    public array $orderNos;
    public string $orderNo;
    public string $pdf;
    public int $size;
    public string $fileName;
    public string $contentDisposition;
    public string $message;

    /** @param list<string> $orderNos */
    public function __construct(
        #[Input] array $orderNos,
        #[Inject] AdminSession $adminSession,
        #[Inject] OrderQueryInterface $orderQuery,
        #[Inject] OrderItemQueryInterface $orderItems,
        #[Inject] ShippingAddressStorageInterface $shippingAddresses,
        #[Inject] OrderPdfCompatibilityInterface $pdfCompatibility,
    ) {
        if ($orderNos === []) {
            throw new OrderNosFormatException();
        }

        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $orders = [];
        $itemsByOrderNo = [];
        $shippingByOrderNo = [];
        foreach ($orderNos as $orderNo) {
            $order = $orderQuery->byOrderNo($orderNo);
            if (! $order instanceof FinalizedOrderEntity) {
                throw new OrderNotFoundException();
            }

            $orders[] = $order;
            $itemsByOrderNo[$orderNo] = $orderItems->listByOrderNo($orderNo);
            $shippingByOrderNo[$orderNo] = $shippingAddresses->byOrderNo($orderNo);
        }

        $document = $pdfCompatibility->render($orders, $itemsByOrderNo, $shippingByOrderNo);

        $this->orderNos = $orderNos;
        $this->orderNo = $orderNos[0];
        $this->pdf = $document->content;
        $this->size = $document->size;
        $this->fileName = $document->fileName;
        $this->contentDisposition = $document->contentDisposition;
        $this->message = 'PDF export completed.';
    }
}
