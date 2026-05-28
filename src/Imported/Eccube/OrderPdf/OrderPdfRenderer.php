<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Imported\Eccube\OrderPdf;

use RuntimeException;
use setasign\Fpdi\Tcpdf\Fpdi;

use function array_slice;
use function count;
use function date;
use function file_exists;
use function number_format;
use function sprintf;
use function strlen;
use function str_replace;
use function substr;
use function trim;

/**
 * Isolated EC-CUBE-compatible delivery-note renderer.
 *
 * This class intentionally keeps the TCPDF/FPDI API and EC-CUBE template
 * coordinates out of Be/BEAR resources. It uses the EC-CUBE admin PDF
 * template assets already deployed under public/template/admin/assets/pdf.
 */
final class OrderPdfRenderer extends Fpdi
{
    private const FONT = 'cid0jp';

    public function __construct(private readonly string $templateDir)
    {
        parent::__construct();

        $this->SetCreator('BeMart EC-CUBE compatibility');
        $this->SetAuthor('BeMart');
        $this->SetTitle('納品書');
        $this->SetMargins(15, 20, 15);
        $this->SetAutoPageBreak(true, 18);
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->SetFont(self::FONT, '', 10);
    }

    /**
     * @param non-empty-list<array{
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
     * }> $orders
     */
    public function render(array $orders): string
    {
        foreach ($orders as $order) {
            $this->renderOrder($order);
        }

        /** @var string $content */
        $content = $this->Output('', 'S');

        return $content;
    }

    /**
     * @param array{
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
     * } $order
     */
    private function renderOrder(array $order): void
    {
        $template = $this->templateDir . '/nouhinsyo.pdf';
        if (! file_exists($template)) {
            throw new RuntimeException(sprintf('PDF template not found: %s', $template));
        }

        $this->setSourceFile($template);
        $this->AddPage('P', 'A4');
        $templatePage = $this->importPage(1);
        $this->useTemplate($templatePage, 0, 0, 210, 297, true);

        $shipping = $order['shipping'];
        $customerName = $shipping === null
            ? 'Customer ' . $order['customerId']
            : trim($shipping['name01'] . ' ' . $shipping['name02']);
        if ($customerName === '') {
            $customerName = 'Customer ' . $order['customerId'];
        }

        $this->textAt(82, 25, '納品書', 16, 'B', 'C', 46);
        $this->textAt(22, 42, $this->addressLine($shipping, 'postal'), 10);
        $this->textAt(22, 48, $this->addressLine($shipping, 'address1'), 10);
        $this->textAt(22, 54, $this->addressLine($shipping, 'address2'), 10);
        $this->textAt(22, 62, $customerName . ' 様', 12, 'B');

        $this->textAt(26, 80, 'このたびはお買上げいただきありがとうございます。', 9);
        $this->textAt(26, 86, '下記の内容にて納品いたしました。', 9);

        $this->textAt(124, 46, 'BeMart', 9, 'B');
        $this->textAt(124, 52, 'EC-CUBE compatibility pilot', 8);
        $this->textAt(124, 58, 'TEL: ' . ($shipping['phoneNumber'] ?? ''), 8);
        $this->textAt(124, 66, '作成日: ' . date('Y年m月d日'), 8);

        $this->textAt(25, 124, $this->formatDate($order['orderDate']), 10);
        $this->textAt(25, 134, $order['orderNo'], 10);
        $this->textAt(122, 95, $this->money($order['paymentTotal']), 15, 'B', 'R', 60);

        $this->renderItems($order['items']);
        $this->renderTotals(
            $order['subtotal'],
            $order['deliveryFeeTotal'],
            $order['charge'],
            $order['discount'],
            $order['tax'],
            $order['total'],
            $order['paymentTotal'],
        );
    }

    /** @param list<array{productName: string, productCode: string, quantity: int, unitPrice: int}> $items */
    private function renderItems(array $items): void
    {
        $this->textAt(20, 151, '商品名 / 商品コード', 8, 'B');
        $this->textAt(130, 151, '数量', 8, 'B', 'C', 12);
        $this->textAt(145, 151, '単価', 8, 'B', 'R', 20);
        $this->textAt(170, 151, '金額(税込)', 8, 'B', 'R', 24);

        $y = 160;
        foreach (array_slice($items, 0, 9) as $item) {
            $name = $item['productName'];
            if ($item['productCode'] !== '') {
                $name .= ' / ' . $item['productCode'];
            }

            $this->textAt(20, $y, $this->shorten($name, 62), 8);
            $this->textAt(130, $y, (string) $item['quantity'], 8, '', 'C', 12);
            $this->textAt(145, $y, $this->money($item['unitPrice']), 8, '', 'R', 20);
            $this->textAt(170, $y, $this->money($item['unitPrice'] * $item['quantity']), 8, '', 'R', 24);
            $y += 7;
        }

        if (count($items) > 9) {
            $this->textAt(20, $y, sprintf('ほか %d 件', count($items) - 9), 8);
        }
    }

    private function renderTotals(
        int $subtotal,
        int $deliveryFeeTotal,
        int $charge,
        int $discount,
        int $tax,
        int $total,
        int $paymentTotal,
    ): void {
        $y = 226;
        $rows = [
            '小計' => $subtotal,
            '送料' => $deliveryFeeTotal,
            '手数料' => $charge,
            '値引き' => -1 * $discount,
            '税額' => $tax,
            '合計' => $total,
            'お支払い合計' => $paymentTotal,
        ];

        foreach ($rows as $label => $amount) {
            $style = $label === 'お支払い合計' ? 'B' : '';
            $this->textAt(128, $y, $label, 8, $style);
            $this->textAt(166, $y, $this->money($amount), 8, $style, 'R', 28);
            $y += 6;
        }
    }

    private function textAt(float $x, float $y, string $text, int $size, string $style = '', string $align = 'L', float $width = 0): void
    {
        $this->SetFont(self::FONT, $style, $size);
        $this->SetXY($x, $y);
        $this->Cell($width, 4, $text, 0, 0, $align);
    }

    /** @param array{name01: string, name02: string, postalCode: string, pref: int, addr01: string, addr02: string, phoneNumber: string}|null $shipping */
    private function addressLine(array|null $shipping, string $part): string
    {
        if ($shipping === null) {
            return '';
        }

        return match ($part) {
            'postal' => $shipping['postalCode'] === '' ? '' : '〒 ' . substr($shipping['postalCode'], 0, 3) . ' - ' . substr($shipping['postalCode'], 3),
            'address1' => $this->prefName($shipping['pref']) . $shipping['addr01'],
            'address2' => $shipping['addr02'],
            default => '',
        };
    }

    private function prefName(int $pref): string
    {
        return $pref === 0 ? '' : 'Pref.' . $pref . ' ';
    }

    private function money(int $amount): string
    {
        return '¥' . number_format($amount);
    }

    private function formatDate(string $value): string
    {
        return str_replace('-', '/', substr($value, 0, 16));
    }

    private function shorten(string $value, int $bytes): string
    {
        if (strlen($value) <= $bytes) {
            return $value;
        }

        return substr($value, 0, $bytes - 3) . '...';
    }
}
