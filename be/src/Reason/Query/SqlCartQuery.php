<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use Override;

use function ctype_digit;
use function str_replace;
use function strrpos;
use function substr;
use function usort;

final class SqlCartQuery implements CartQueryInterface
{
    public function __construct(private readonly MediaQueryExecutor $db) {}

    #[Override]
    public function byCartKey(string $cartKey): CartEntity|null
    {
        $row = $this->db->row('cart_by_key', ['cartKey' => $cartKey]);
        return $row === null ? null : $this->hydrateCart($row);
    }

    /** @return list<CartEntity> */
    #[Override]
    public function bySessionPrefix(string $sessionPrefix): array
    {
        $out = array_map($this->hydrateCart(...), $this->db->rows('cart_by_session_prefix', ['pattern' => $this->escapeLike($sessionPrefix) . '\\_%']));
        usort($out, static fn (CartEntity $a, CartEntity $b): int => $a->saleTypeId <=> $b->saleTypeId);
        return $out;
    }

    /** @param array<string, mixed> $row */
    private function hydrateCart(array $row): CartEntity
    {
        $cartKey = (string) $row['cart_key'];
        $saleTypeId = $this->parseSaleTypeId($cartKey);
        return new CartEntity(
            cartKey: $cartKey,
            saleTypeId: $saleTypeId,
            saleTypeName: $this->lookupSaleTypeName($saleTypeId),
            items: $this->fetchItems((int) $row['id']),
            totalPrice: (int) $row['total_price'],
            deliveryFeeTotal: (int) $row['delivery_fee_total'],
            preOrderId: (string) ($row['pre_order_id'] ?? ''),
        );
    }

    private function parseSaleTypeId(string $cartKey): int
    {
        $position = strrpos($cartKey, '_');
        if ($position === false) {
            return 0;
        }
        $suffix = substr($cartKey, $position + 1);
        return ctype_digit($suffix) ? (int) $suffix : 0;
    }

    private function lookupSaleTypeName(int $saleTypeId): string
    {
        $row = $this->db->row('cart_sale_type', ['id' => $saleTypeId]);
        return $row === null ? '' : (string) $row['name'];
    }

    /** @return list<CartItemEntity> */
    private function fetchItems(int $cartId): array
    {
        return array_map(
            static fn (array $row): CartItemEntity => new CartItemEntity(
                productCode: (string) ($row['product_code'] ?? ''),
                quantity: (int) $row['quantity'],
                price: (int) $row['price'],
                productClassId: (int) $row['product_class_id'],
                productId: (int) $row['product_id'],
                productName: (string) ($row['product_name'] ?? ''),
                mainImage: $row['main_image'] !== null ? (string) $row['main_image'] : null,
                classCategoryName1: $row['class_category_name1'] !== null ? (string) $row['class_category_name1'] : null,
                className1: $row['class_name1'] !== null ? (string) $row['class_name1'] : null,
                classCategoryName2: $row['class_category_name2'] !== null ? (string) $row['class_category_name2'] : null,
                className2: $row['class_name2'] !== null ? (string) $row['class_name2'] : null,
            ),
            $this->db->rows('cart_items', ['cartId' => $cartId]),
        );
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
