<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlCartQuery;

final class SqlCartQueryTest extends AbstractSqlTestCase
{
    public function testByCartKeyHydratesHeaderAndItems(): void
    {
        $productA = $this->insertProduct(['product_code' => 'CART-A', 'price02' => 500]);
        $productB = $this->insertProduct(['product_code' => 'CART-B', 'price02' => 750]);
        $classA = $this->defaultProductClassId($productA);
        $classB = $this->defaultProductClassId($productB);

        $cart = $this->insertCart([
            'cart_key' => 'session-hit_3',
            'pre_order_id' => 'pre-hit-001',
            'total_price' => 2000,
            'delivery_fee_total' => 600,
        ]);
        $this->insertCartItem($cart['id'], $classA, ['price' => 500, 'quantity' => 2]);
        $this->insertCartItem($cart['id'], $classB, ['price' => 750, 'quantity' => 1]);

        $query = new SqlCartQuery($this->pdo);
        $result = $query->byCartKey('session-hit_3');

        $this->assertInstanceOf(CartEntity::class, $result);
        $this->assertSame('session-hit_3', $result->cartKey);
        // saleTypeId parsed from cart_key suffix (3).
        $this->assertSame(3, $result->saleTypeId);
        // mtb_sale_type empty in structure-only schema → '' fallback.
        $this->assertSame('', $result->saleTypeName);
        $this->assertSame(2000, $result->totalPrice);
        $this->assertSame(600, $result->deliveryFeeTotal);
        $this->assertSame('pre-hit-001', $result->preOrderId);

        $this->assertCount(2, $result->items);
        $this->assertSame('CART-A', $result->items[0]->productCode);
        $this->assertSame(2, $result->items[0]->quantity);
        $this->assertSame(500, $result->items[0]->price);
        $this->assertSame('CART-B', $result->items[1]->productCode);
        $this->assertSame(1, $result->items[1]->quantity);
        $this->assertSame(750, $result->items[1]->price);
    }

    public function testByCartKeyReturnsNullWhenMissing(): void
    {
        $this->insertCart(['cart_key' => 'somebody-else_1']);

        $query = new SqlCartQuery($this->pdo);
        $this->assertNull($query->byCartKey('absent_1'));
    }

    public function testBySessionPrefixReturnsCartsSortedBySaleTypeId(): void
    {
        // Three carts under the same session prefix, two unrelated
        // carts that must NOT leak into the result.
        $this->insertCart(['cart_key' => 'multi_2']);
        $this->insertCart(['cart_key' => 'multi_1']);
        $this->insertCart(['cart_key' => 'multi_3']);
        $this->insertCart(['cart_key' => 'other_1']);
        $this->insertCart(['cart_key' => 'multi-extra_1']); // prefix is "multi-extra", NOT "multi"

        $query = new SqlCartQuery($this->pdo);
        $carts = $query->bySessionPrefix('multi');

        $this->assertCount(3, $carts);
        $saleTypeIds = array_map(static fn (CartEntity $c) => $c->saleTypeId, $carts);
        $this->assertSame([1, 2, 3], $saleTypeIds);
        $cartKeys = array_map(static fn (CartEntity $c) => $c->cartKey, $carts);
        $this->assertSame(['multi_1', 'multi_2', 'multi_3'], $cartKeys);
    }

    public function testBySessionPrefixReturnsEmptyWhenSessionHasNoCarts(): void
    {
        // Seed an unrelated cart so the filter is doing real work.
        $this->insertCart(['cart_key' => 'unrelated_1']);

        $query = new SqlCartQuery($this->pdo);
        $this->assertSame([], $query->bySessionPrefix('ghost-session'));
    }

    public function testParsesMultiDigitSaleTypeIdFromCartKey(): void
    {
        // Suffix may legitimately be multi-digit (saleTypeId can be 10+
        // in custom EC-CUBE installs). Prefix here also contains
        // underscores — parser must split on the LAST `_`.
        $this->insertCart(['cart_key' => 'sess_abc_xyz_127']);

        $query = new SqlCartQuery($this->pdo);
        $cart = $query->byCartKey('sess_abc_xyz_127');

        $this->assertNotNull($cart);
        $this->assertSame(127, $cart->saleTypeId);
    }

    public function testItemsAreOrderedByInsertionId(): void
    {
        $productA = $this->insertProduct(['product_code' => 'ORD-A', 'price02' => 100]);
        $productB = $this->insertProduct(['product_code' => 'ORD-B', 'price02' => 200]);
        $productC = $this->insertProduct(['product_code' => 'ORD-C', 'price02' => 300]);

        $cart = $this->insertCart(['cart_key' => 'ordering_1']);
        // Insert deliberately out of "logical" order — query must
        // surface them in insertion (id ASC) order.
        $this->insertCartItem($cart['id'], $this->defaultProductClassId($productC), ['price' => 300]);
        $this->insertCartItem($cart['id'], $this->defaultProductClassId($productA), ['price' => 100]);
        $this->insertCartItem($cart['id'], $this->defaultProductClassId($productB), ['price' => 200]);

        $query = new SqlCartQuery($this->pdo);
        $cart = $query->byCartKey('ordering_1');

        $this->assertNotNull($cart);
        $codes = array_map(
            static fn ($item) => $item->productCode,
            $cart->items,
        );
        $this->assertSame(['ORD-C', 'ORD-A', 'ORD-B'], $codes);
    }
}
