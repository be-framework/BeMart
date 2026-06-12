<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Query;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryMailEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryShippingEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderHistoryQueryInterface;
use Override;

use function array_map;
use function is_array;
use function is_string;

/** Browser Fake order-history detail backed by SessionOrderStorage. */
final readonly class SessionOrderHistoryQuery implements OrderHistoryQueryInterface
{
    public function __construct(
        private SessionOrderStorage $orders,
    ) {
    }

    #[Override]
    public function item(string $orderNo): OrderHistoryEntity|null
    {
        $order = $this->orders->byOrderNo($orderNo);
        if (! $order instanceof FinalizedOrderEntity) {
            return null;
        }

        /** @var mixed $snapshot */
        $snapshot = $order->customerSnapshot;
        $snapshot = is_array($snapshot) ? $snapshot : [];

        $items = array_map(
            static fn (array $item): OrderHistoryItemEntity => new OrderHistoryItemEntity(
                productCode: (string) ($item['productCode'] ?? ''),
                productName: (string) ($item['productName'] ?? ''),
                quantity: (int) ($item['quantity'] ?? 0),
                unitPrice: (int) ($item['unitPrice'] ?? 0),
            ),
            $this->orders->itemRowsByOrderNo($orderNo),
        );

        return new OrderHistoryEntity(
            orderNo: $order->orderNo,
            customerId: $order->customerId,
            message: $this->stringValue($snapshot, 'message', ''),
            paymentMethod: $this->paymentMethod($order->paymentMethodId),
            subtotal: $order->subtotal,
            deliveryFeeTotal: $order->deliveryFeeTotal,
            charge: $order->charge,
            discount: $order->discount,
            tax: $order->tax,
            total: $order->total,
            paymentTotal: $order->paymentTotal,
            addPoint: $order->addPoint,
            usePoint: $order->usePoint,
            orderStatus: $order->orderStatus,
            orderDate: $order->orderDate,
            paymentDate: $order->paymentDate,
            shippings: [new OrderHistoryShippingEntity(
                name01: $this->stringValue($snapshot, 'name01', '山田'),
                name02: $this->stringValue($snapshot, 'name02', '太郎'),
                kana01: $this->stringValue($snapshot, 'kana01', 'ヤマダ'),
                kana02: $this->stringValue($snapshot, 'kana02', 'タロウ'),
                postalCode: $this->stringValue($snapshot, 'postalCode', '1500001'),
                prefName: $this->prefName($snapshot),
                addr01: $this->stringValue($snapshot, 'addr01', '東京都渋谷区神宮前'),
                addr02: $this->stringValue($snapshot, 'addr02', '1-2-3 IDEAビル'),
                phoneNumber: $this->stringValue($snapshot, 'phoneNumber', '0312345678'),
                deliveryName: '通常配送',
                deliveryDate: '',
                deliveryTime: '',
                items: $items,
            )],
            mailHistories: [new OrderHistoryMailEntity(
                sendDate: $order->orderDate,
                mailSubject: 'ご注文を受け付けました',
                mailBody: "IDEA STORE のご注文を受け付けました。\n発送準備が整い次第、あらためてお知らせします。",
            )],
        );
    }

    /** @param array<string, mixed> $snapshot */
    private function stringValue(array $snapshot, string $key, string $default): string
    {
        $value = $snapshot[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : $default;
    }

    /** @param array<string, mixed> $snapshot */
    private function prefName(array $snapshot): string
    {
        $prefName = $this->stringValue($snapshot, 'prefName', '');
        if ($prefName !== '') {
            return $prefName;
        }

        return match ((string) ($snapshot['pref'] ?? '')) {
            '13' => '東京都',
            '14' => '神奈川県',
            '11' => '埼玉県',
            '12' => '千葉県',
            default => '',
        };
    }

    private function paymentMethod(int $paymentMethodId): string
    {
        return match ($paymentMethodId) {
            2 => 'Fake決済カード',
            1 => '代金引換',
            default => 'お支払い方法未設定',
        };
    }
}
