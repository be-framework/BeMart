<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Query;

use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Override;

use function array_filter;
use function array_map;
use function array_slice;
use function array_values;
use function is_array;
use function is_string;
use function session_status;
use function usort;

use const PHP_SESSION_ACTIVE;

/**
 * Session-backed Fake order storage for browser checkout contexts.
 *
 * Static FakeQuery command fixtures cannot persist the processing pre-order
 * that /shopping materialises, so the browser POST /shopping/confirm cannot
 * resolve the just-created preOrderId on the next request. This adapter keeps
 * only the browser Fake context mutable, mirroring the session-backed cart.
 */
final class SessionOrderStorage implements OrderQueryInterface, OrderCommandInterface
{
    private const SESSION_KEY = 'bemart_fake_orders';

    /** @var array<string, array<string, mixed>> */
    private static array $fallback = [];

    public function __construct(
        private readonly SessionCartStorage $cartStorage,
        private readonly CustomerSession $session,
    ) {
    }

    #[Override]
    public function byPreOrderId(string $preOrderId): OrderEntity|null
    {
        $row = $this->processingRowByPreOrderId($preOrderId);
        $cart = $this->cartStorage->findByPreOrderId($preOrderId);
        if ($row === null && $cart === null) {
            return null;
        }

        /** @var mixed $snapshot */
        $snapshot = $row['customerSnapshot'] ?? [];

        return new OrderEntity(
            preOrderId: $preOrderId,
            customerId: $this->customerId($row),
            paymentMethodId: (int) ($row['paymentMethodId'] ?? 1),
            items: $cart?->items ?? [],
            deliveryFeeTotal: $cart?->deliveryFeeTotal ?? (int) ($row['deliveryFeeTotal'] ?? 0),
            customerSnapshot: is_array($snapshot) ? $snapshot : [],
        );
    }

    #[Override]
    public function byOrderNo(string $orderNo): FinalizedOrderEntity|null
    {
        $row = $this->rows()[$orderNo] ?? null;
        if (! is_array($row) || (int) ($row['orderStatus'] ?? 0) === FinalizedOrderEntity::STATUS_PROCESSING) {
            return null;
        }

        return self::finalizedFromRow($row);
    }

    /** @return list<FinalizedOrderEntity> */
    #[Override]
    public function listByCustomer(string $customerId, int $limit = 10, int $offset = 0): array
    {
        return $this->sortedFinalizedOrders(
            static fn (array $row): bool => (string) ($row['customerId'] ?? '') === $customerId,
            $limit,
            $offset,
        );
    }

    /** @return list<FinalizedOrderEntity> */
    #[Override]
    public function list(int $limit = 50, int $offset = 0): array
    {
        return $this->sortedFinalizedOrders(static fn (array $row): bool => true, $limit, $offset);
    }

    #[Override]
    public function register(FinalizedOrderEntity $order): void
    {
        $rows = $this->rows();
        $rows[$order->orderNo] = $this->rowFromFinalized($order);
        $this->writeRows($rows);
    }

    #[Override]
    public function update(FinalizedOrderEntity $order): void
    {
        $this->register($order);
    }

    #[Override]
    public function updateStatus(string $orderNo, int $newStatus): void
    {
        $rows = $this->rows();
        if (! isset($rows[$orderNo])) {
            return;
        }

        $rows[$orderNo]['orderStatus'] = $newStatus;
        $this->writeRows($rows);
    }

    /** @return list<array{productCode: string, productName: string, quantity: int, unitPrice: int}> */
    public function itemRowsByOrderNo(string $orderNo): array
    {
        $row = $this->rows()[$orderNo] ?? null;
        if (! is_array($row)) {
            return [];
        }

        /** @var mixed $items */
        $items = $row['items'] ?? [];
        if (! is_array($items) || $items === []) {
            $cart = $this->cartStorage->findByPreOrderId((string) ($row['preOrderId'] ?? ''));
            $items = $cart?->items ?? [];
        }

        return array_values(array_map(
            static fn (mixed $item): array => $item instanceof CartItemEntity ? [
                'productCode' => $item->productCode,
                'productName' => $item->productName,
                'quantity' => $item->quantity,
                'unitPrice' => $item->price,
            ] : (is_array($item) ? [
                'productCode' => (string) ($item['productCode'] ?? ''),
                'productName' => (string) ($item['productName'] ?? ''),
                'quantity' => (int) ($item['quantity'] ?? 0),
                'unitPrice' => (int) ($item['unitPrice'] ?? 0),
            ] : ['productCode' => '', 'productName' => '', 'quantity' => 0, 'unitPrice' => 0]),
            $items,
        ));
    }

    /** @return array<string, mixed>|null */
    private function processingRowByPreOrderId(string $preOrderId): array|null
    {
        foreach ($this->rows() as $row) {
            if (
                (string) ($row['preOrderId'] ?? '') === $preOrderId
                && (int) ($row['orderStatus'] ?? 0) === FinalizedOrderEntity::STATUS_PROCESSING
            ) {
                return $row;
            }
        }

        return null;
    }

    /** @param array<string, mixed>|null $row */
    private function customerId(array|null $row): string
    {
        $customerId = is_array($row) ? ($row['customerId'] ?? null) : null;
        if (is_string($customerId) && $customerId !== '') {
            return $customerId;
        }

        return $this->session->customerId ?? '';
    }

    /** @return array<string, array<string, mixed>> */
    private function rows(): array
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            /** @var mixed $rows */
            $rows = $_SESSION[self::SESSION_KEY] ?? [];

            return is_array($rows) ? $rows : [];
        }

        return self::$fallback;
    }

    /** @param array<string, array<string, mixed>> $rows */
    private function writeRows(array $rows): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[self::SESSION_KEY] = $rows;

            return;
        }

        self::$fallback = $rows;
    }

    /**
     * @param callable(array<string, mixed>): bool $filter
     * @return list<FinalizedOrderEntity>
     */
    private function sortedFinalizedOrders(callable $filter, int $limit, int $offset): array
    {
        $rows = array_values(array_filter(
            $this->rows(),
            static fn (array $row): bool => (int) ($row['orderStatus'] ?? 0) !== FinalizedOrderEntity::STATUS_PROCESSING,
        ));
        $rows = array_values(array_filter($rows, $filter));
        usort(
            $rows,
            static fn (array $a, array $b): int => (string) ($b['orderDate'] ?? '') <=> (string) ($a['orderDate'] ?? ''),
        );

        return array_map(
            static fn (array $row): FinalizedOrderEntity => self::finalizedFromRow($row),
            array_slice($rows, $offset, $limit),
        );
    }

    /** @return array<string, mixed> */
    private function rowFromFinalized(FinalizedOrderEntity $order): array
    {
        return [
            'orderNo' => $order->orderNo,
            'preOrderId' => $order->preOrderId,
            'customerId' => $order->customerId,
            'paymentMethodId' => $order->paymentMethodId,
            'subtotal' => $order->subtotal,
            'deliveryFeeTotal' => $order->deliveryFeeTotal,
            'charge' => $order->charge,
            'discount' => $order->discount,
            'tax' => $order->tax,
            'total' => $order->total,
            'paymentTotal' => $order->paymentTotal,
            'addPoint' => $order->addPoint,
            'usePoint' => $order->usePoint,
            'orderStatus' => $order->orderStatus,
            'orderDate' => $order->orderDate,
            'paymentDate' => $order->paymentDate,
            'customerSnapshot' => $order->customerSnapshot,
            'items' => array_map(
                static fn (CartItemEntity $item): array => [
                    'productCode' => $item->productCode,
                    'productName' => $item->productName,
                    'quantity' => $item->quantity,
                    'unitPrice' => $item->price,
                ],
                $this->cartStorage->findByPreOrderId($order->preOrderId)?->items ?? [],
            ),
        ];
    }

    /** @param array<string, mixed> $row */
    private static function finalizedFromRow(array $row): FinalizedOrderEntity
    {
        /** @var mixed $snapshot */
        $snapshot = $row['customerSnapshot'] ?? [];

        return new FinalizedOrderEntity(
            orderNo: (string) ($row['orderNo'] ?? ''),
            preOrderId: (string) ($row['preOrderId'] ?? ''),
            customerId: (string) ($row['customerId'] ?? ''),
            paymentMethodId: (int) ($row['paymentMethodId'] ?? 1),
            subtotal: (int) ($row['subtotal'] ?? 0),
            deliveryFeeTotal: (int) ($row['deliveryFeeTotal'] ?? 0),
            charge: (int) ($row['charge'] ?? 0),
            discount: (int) ($row['discount'] ?? 0),
            tax: (int) ($row['tax'] ?? 0),
            total: (int) ($row['total'] ?? 0),
            paymentTotal: (int) ($row['paymentTotal'] ?? 0),
            addPoint: (int) ($row['addPoint'] ?? 0),
            usePoint: (int) ($row['usePoint'] ?? 0),
            orderStatus: (int) ($row['orderStatus'] ?? FinalizedOrderEntity::STATUS_NEW),
            orderDate: (string) ($row['orderDate'] ?? ''),
            paymentDate: (string) ($row['paymentDate'] ?? ''),
            customerSnapshot: is_array($snapshot) ? $snapshot : [],
        );
    }
}
