<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use Override;
use RuntimeException;

use function sprintf;
use function str_replace;

final class SqlCartCommand implements CartCommandInterface
{
    public function __construct(private readonly InternalDbQueryInterface $db) {}

    #[Override]
    public function save(CartEntity $cart): void
    {
        $resolved = [];
        foreach ($cart->items as $item) {
            $resolved[] = [
                'productClassId' => $this->resolveProductClassId($item->productCode),
                'quantity' => $item->quantity,
                'price' => $item->price,
            ];
        }
        $this->db->cart_delete_by_key(cartKey: $cart->cartKey);
        $this->db->cart_insert(
            cartKey: $cart->cartKey,
            preOrderId: $cart->preOrderId === '' ? null : $cart->preOrderId,
            totalPrice: $cart->totalPrice,
            deliveryFeeTotal: $cart->deliveryFeeTotal,
        );
        $cartId = (int) ($this->db->cart_last_id()['id'] ?? 0);
        foreach ($resolved as $item) {
            $this->db->cart_item_insert(productClassId: $item['productClassId'], cartId: $cartId, price: $item['price'], quantity: $item['quantity']);
        }
    }

    #[Override]
    public function clearByPreOrderId(string $preOrderId): void
    {
        $this->db->cart_clear_pre_order(preOrderId: $preOrderId);
    }

    #[Override]
    public function clearBySessionPrefix(string $sessionPrefix): void
    {
        $this->db->cart_clear_session_prefix(pattern: $this->escapeLike($sessionPrefix) . '\\_%');
    }

    private function resolveProductClassId(string $productCode): int
    {
        $row = $this->db->cart_resolve_product_class(productCode: $productCode);
        if ($row === null) {
            throw new RuntimeException(sprintf('SqlCartCommand: unknown productCode "%s".', $productCode));
        }
        return (int) $row['id'];
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
