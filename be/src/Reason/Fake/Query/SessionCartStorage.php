<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Query;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\ProductClassEntity;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductClassQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\Result\SavedCart;
use Override;

use function array_filter;
use function array_map;
use function array_values;
use function is_array;
use function is_int;
use function is_string;
use function ksort;
use function session_status;
use function str_starts_with;

use const PHP_SESSION_ACTIVE;

/**
 * Session-backed Fake cart storage for browser HTML contexts.
 *
 * Ray.FakeQuery fixtures are intentionally static: command calls such as
 * CartCommandInterface::save() do not mutate the JSONL read fixtures used by
 * the next HTTP request. That is fine for pure resource tests, but the
 * browser storefront must carry an added cart item across the POST → 303 →
 * GET /cart redirect. This storage is installed only for html+test Fake
 * contexts and keeps the same CartQueryInterface / CartCommandInterface
 * contract while persisting carts in the PHP session.
 */
final class SessionCartStorage implements CartQueryInterface, CartCommandInterface
{
    private const SESSION_KEY = 'bemart_fake_carts';

    /** @var array<string, array<string, mixed>> */
    private static array $fallback = [];

    public function __construct(
        private readonly ProductClassQueryInterface $productClassQuery,
        private readonly ProductQueryInterface $productQuery,
    ) {
    }

    #[Override]
    public function item(string $cartKey): CartEntity|null
    {
        $rows = $this->rows();
        $row = $rows[$cartKey] ?? null;

        return is_array($row) ? $this->cartFromRow($row) : null;
    }

    /** @return list<CartEntity> */
    #[Override]
    public function listBySessionPrefix(string $sessionPrefix): array
    {
        $prefix = $sessionPrefix . '_';
        $rows = array_filter(
            $this->rows(),
            static fn (array $row, string $cartKey): bool => str_starts_with($cartKey, $prefix),
            ARRAY_FILTER_USE_BOTH,
        );
        ksort($rows);

        return array_values(array_map(fn (array $row): CartEntity => $this->cartFromRow($row), $rows));
    }

    public function findByPreOrderId(string $preOrderId): CartEntity|null
    {
        foreach ($this->rows() as $row) {
            if ((string) ($row['preOrderId'] ?? '') === $preOrderId) {
                return $this->cartFromRow($row);
            }
        }

        return null;
    }

    #[Override]
    public function save(CartEntity $cart): SavedCart
    {
        $rows = $this->rows();
        $rows[$cart->cartKey] = $this->cartToRow($cart);
        $this->writeRows($rows);

        return new SavedCart(true);
    }

    #[Override]
    public function clearByPreOrderId(string $preOrderId): void
    {
        $rows = array_filter(
            $this->rows(),
            static fn (array $row): bool => (string) ($row['preOrderId'] ?? '') !== $preOrderId,
        );
        $this->writeRows($rows);
    }

    #[Override]
    public function clearBySessionPrefix(string $sessionPrefix): void
    {
        $prefix = $sessionPrefix . '_';
        $rows = array_filter(
            $this->rows(),
            static fn (array $row, string $cartKey): bool => ! str_starts_with($cartKey, $prefix),
            ARRAY_FILTER_USE_BOTH,
        );
        $this->writeRows($rows);
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

    /** @return array<string, mixed> */
    private function cartToRow(CartEntity $cart): array
    {
        return [
            'cartKey' => $cart->cartKey,
            'saleTypeId' => $cart->saleTypeId,
            'saleTypeName' => $cart->saleTypeName,
            'items' => array_map(fn (CartItemEntity $item): array => $this->itemToRow($item), $cart->items),
            'totalPrice' => $cart->totalPrice,
            'deliveryFeeTotal' => $cart->deliveryFeeTotal,
            'preOrderId' => $cart->preOrderId,
        ];
    }

    /** @return array<string, mixed> */
    private function itemToRow(CartItemEntity $item): array
    {
        return [
            'productCode' => $item->productCode,
            'quantity' => $item->quantity,
            'price' => $item->price,
            'productClassId' => $item->productClassId,
            'productId' => $item->productId,
            'productName' => $item->productName,
            'mainImage' => $item->mainImage,
            'classCategoryName1' => $item->classCategoryName1,
            'className1' => $item->className1,
            'classCategoryName2' => $item->classCategoryName2,
            'className2' => $item->className2,
        ];
    }

    /** @param array<string, mixed> $row */
    private function cartFromRow(array $row): CartEntity
    {
        /** @var mixed $items */
        $items = $row['items'] ?? [];
        $items = is_array($items) ? $items : [];

        return new CartEntity(
            cartKey: (string) ($row['cartKey'] ?? ''),
            saleTypeId: (int) ($row['saleTypeId'] ?? 1),
            saleTypeName: (string) ($row['saleTypeName'] ?? '通常販売'),
            items: array_values(array_map(fn (mixed $item): CartItemEntity => $this->itemFromRow(is_array($item) ? $item : []), $items)),
            totalPrice: (int) ($row['totalPrice'] ?? 0),
            deliveryFeeTotal: (int) ($row['deliveryFeeTotal'] ?? 0),
            preOrderId: (string) ($row['preOrderId'] ?? ''),
        );
    }

    /** @param array<string, mixed> $row */
    private function itemFromRow(array $row): CartItemEntity
    {
        $productCode = (string) ($row['productCode'] ?? '');
        $productClass = $productCode !== '' ? $this->productClassQuery->item($productCode) : null;
        $product = $productCode !== '' ? $this->productQuery->item($productCode) : null;

        return new CartItemEntity(
            productCode: $productCode,
            quantity: $this->intValue($row['quantity'] ?? 0),
            price: $this->intValue($row['price'] ?? ($productClass instanceof ProductClassEntity ? $productClass->price02 : 0)),
            productClassId: $this->intValue($row['productClassId'] ?? 0),
            productId: $this->intValue($row['productId'] ?? 0),
            productName: $this->stringValue($row['productName'] ?? '', $productClass instanceof ProductClassEntity ? $productClass->productName : ''),
            mainImage: $this->nullableString($row['mainImage'] ?? ($product instanceof ProductEntity ? $product->imagePath : null)),
            classCategoryName1: $this->nullableString($row['classCategoryName1'] ?? null),
            className1: $this->nullableString($row['className1'] ?? null),
            classCategoryName2: $this->nullableString($row['classCategoryName2'] ?? null),
            className2: $this->nullableString($row['className2'] ?? null),
        );
    }

    private function intValue(mixed $value): int
    {
        return is_int($value) ? $value : (int) $value;
    }

    private function stringValue(mixed $value, string $default): string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return $default;
    }

    private function nullableString(mixed $value): string|null
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
