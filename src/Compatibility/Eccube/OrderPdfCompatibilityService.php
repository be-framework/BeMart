<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\ShippingAddressEntity;
use MyVendor\BeMart\Be\Reason\Service\OrderPdfCompatibilityInterface;
use MyVendor\BeMart\Be\Reason\Service\OrderPdfDocument;
use MyVendor\BeMart\Imported\Eccube\OrderPdf\OrderPdfRenderer;
use Override;

use function count;
use function sprintf;

/**
 * BeMart-side adapter for EC-CUBE-compatible order PDF generation.
 *
 * BEAR Resources and Be Finals depend only on
 * OrderPdfCompatibilityInterface; TCPDF/FPDI and EC-CUBE template
 * coordinates stay behind this compatibility boundary.
 */
final readonly class OrderPdfCompatibilityService implements OrderPdfCompatibilityInterface
{
    public function __construct(private string|null $templateDir = null)
    {
    }

    /**
     * @param non-empty-list<FinalizedOrderEntity>             $orders
     * @param array<string, list<OrderItemEntity>>             $itemsByOrderNo
     * @param array<string, ShippingAddressEntity|null>        $shippingByOrderNo
     */
    #[Override]
    public function render(
        array $orders,
        array $itemsByOrderNo,
        array $shippingByOrderNo,
    ): OrderPdfDocument {
        $renderer = new OrderPdfRenderer($this->templateDir ?? dirname(__DIR__, 3) . '/public/template/admin/assets/pdf');
        $content = $renderer->render($this->normalizeOrders($orders, $itemsByOrderNo, $shippingByOrderNo));
        $fileName = count($orders) === 1 ? sprintf('nouhinsyo-No%s.pdf', $orders[0]->orderNo) : 'nouhinsyo.pdf';

        return new OrderPdfDocument(
            content: $content,
            fileName: $fileName,
            contentDisposition: 'attachment; filename="' . $fileName . '"',
        );
    }

    /**
     * @param non-empty-list<FinalizedOrderEntity>             $orders
     * @param array<string, list<OrderItemEntity>>             $itemsByOrderNo
     * @param array<string, ShippingAddressEntity|null>        $shippingByOrderNo
     * @return non-empty-list<array{
     *     orderNo: string,
     *     customerId: string,
     *     subtotal: int,
     *     deliveryFeeTotal: int,
     *     charge: int,
     *     discount: int,
     *     tax: int,
     *     total: int,
     *     paymentTotal: int,
     *     orderDate: string,
     *     items: list<array{productName: string, productCode: string, quantity: int, unitPrice: int}>,
     *     shipping: array{name01: string, name02: string, postalCode: string, pref: int, addr01: string, addr02: string, phoneNumber: string}|null
     * }>
     */
    private function normalizeOrders(array $orders, array $itemsByOrderNo, array $shippingByOrderNo): array
    {
        $normalized = [];
        foreach ($orders as $order) {
            $shipping = $shippingByOrderNo[$order->orderNo] ?? null;
            $normalized[] = [
                'orderNo' => $order->orderNo,
                'customerId' => $order->customerId,
                'subtotal' => $order->subtotal,
                'deliveryFeeTotal' => $order->deliveryFeeTotal,
                'charge' => $order->charge,
                'discount' => $order->discount,
                'tax' => $order->tax,
                'total' => $order->total,
                'paymentTotal' => $order->paymentTotal,
                'orderDate' => $order->orderDate,
                'items' => $this->normalizeItems($itemsByOrderNo[$order->orderNo] ?? []),
                'shipping' => $shipping instanceof ShippingAddressEntity ? [
                    'name01' => $shipping->name01,
                    'name02' => $shipping->name02,
                    'postalCode' => $shipping->postalCode,
                    'pref' => $shipping->pref,
                    'addr01' => $shipping->addr01,
                    'addr02' => $shipping->addr02,
                    'phoneNumber' => $shipping->phoneNumber,
                ] : null,
            ];
        }

        return $normalized;
    }

    /**
     * @param list<OrderItemEntity> $items
     * @return list<array{productName: string, productCode: string, quantity: int, unitPrice: int}>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];
        foreach ($items as $item) {
            $normalized[] = [
                'productName' => $item->productName,
                'productCode' => $item->productCode,
                'quantity' => $item->quantity,
                'unitPrice' => $item->unitPrice,
            ];
        }

        return $normalized;
    }
}
