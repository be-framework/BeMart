<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Query\Factory\CartFactory;
use Override;

final class SqlCartQuery implements CartQueryInterface
{
    private CartFactory $factory;

    public function __construct(private readonly MediaQueryExecutor $db)
    {
        $this->factory = new CartFactory();
    }

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
        return array_map($this->hydrateCart(...), $this->db->rows('cart_by_session_prefix', ['sessionPrefix' => $sessionPrefix]));
    }

    /** @param array<string, mixed> $row */
    private function hydrateCart(array $row): CartEntity
    {
        return $this->factory->factory(
            (string) $row['cart_key'],
            $row['sale_type_id'],
            $row['sale_type_name'] === null ? null : (string) $row['sale_type_name'],
            $row['items_json'] === null ? null : (string) $row['items_json'],
            $row['total_price'],
            $row['delivery_fee_total'],
            $row['pre_order_id'] === null ? null : (string) $row['pre_order_id'],
        );
    }
}
