<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Final\CartsFetched;
use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlCartQuery;

/**
 * End-to-end goCart smoke against the SQL Cart backend.
 *
 * Wires {@see SqlCartQuery} directly into the {@see CartsFetched}
 * Final — NO injector, NO Becoming chain. The Final is a
 * `readonly final class` with all dependencies on its constructor,
 * so direct instantiation is the cleanest path for an integration
 * smoke that wants to assert the raw projection shape.
 *
 * Production DI (`AppModule`) is intentionally NOT exercised — it
 * remains bound to Fake* implementations. Phase 2b will swap the
 * bindings once every read-side query has a Sql counterpart.
 */
final class CartsFetchedSqlIntegrationTest extends AbstractSqlTestCase
{
    public function testHappyPathProjectsTotalsAcrossTwoCarts(): void
    {
        // 4 products → 2 per cart.
        $a1 = $this->insertProduct(['product_code' => 'CART-X-1', 'price02' => 100]);
        $a2 = $this->insertProduct(['product_code' => 'CART-X-2', 'price02' => 200]);
        $b1 = $this->insertProduct(['product_code' => 'CART-X-3', 'price02' => 300]);
        $b2 = $this->insertProduct(['product_code' => 'CART-X-4', 'price02' => 400]);

        // Cart 1 — saleTypeId 1, totalPrice 500, deliveryFee 200.
        $cart1 = $this->insertCart([
            'cart_key' => 'goCart-session_1',
            'pre_order_id' => 'pre-goCart-1',
            'total_price' => 500,
            'delivery_fee_total' => 200,
        ]);
        $this->insertCartItem($cart1['id'], $this->defaultProductClassId($a1), ['price' => 100, 'quantity' => 1]);
        $this->insertCartItem($cart1['id'], $this->defaultProductClassId($a2), ['price' => 200, 'quantity' => 2]);

        // Cart 2 — saleTypeId 2, totalPrice 1100, deliveryFee 300.
        $cart2 = $this->insertCart([
            'cart_key' => 'goCart-session_2',
            'pre_order_id' => 'pre-goCart-2',
            'total_price' => 1100,
            'delivery_fee_total' => 300,
        ]);
        $this->insertCartItem($cart2['id'], $this->defaultProductClassId($b1), ['price' => 300, 'quantity' => 1]);
        $this->insertCartItem($cart2['id'], $this->defaultProductClassId($b2), ['price' => 400, 'quantity' => 2]);

        // Unrelated cart that must NOT show up under this session.
        $this->insertCart(['cart_key' => 'somebody-else_1']);

        $final = new CartsFetched(
            sessionPrefix: 'goCart-session',
            cartQuery: new SqlCartQuery($this->pdo),
        );

        $this->assertSame(2, $final->cartCount);
        $this->assertSame(1600, $final->totalPrice);
        $this->assertSame(500, $final->deliveryFeeTotal);

        // Sorted by saleTypeId ascending (Cart 1 then Cart 2).
        $this->assertCount(2, $final->carts);
        $saleTypeIds = array_map(static fn (CartEntity $c) => $c->saleTypeId, $final->carts);
        $this->assertSame([1, 2], $saleTypeIds);

        // First cart's items are hydrated with productCode (JOIN
        // through dtb_product_class).
        $cart1Codes = array_map(static fn ($i) => $i->productCode, $final->carts[0]->items);
        sort($cart1Codes);
        $this->assertSame(['CART-X-1', 'CART-X-2'], $cart1Codes);
    }

    public function testEmptySessionProjectsZeroes(): void
    {
        // Seed an unrelated cart so the prefix filter is doing real work.
        $this->insertCart(['cart_key' => 'else_1']);

        $final = new CartsFetched(
            sessionPrefix: 'empty-session',
            cartQuery: new SqlCartQuery($this->pdo),
        );

        $this->assertSame([], $final->carts);
        $this->assertSame(0, $final->cartCount);
        $this->assertSame(0, $final->totalPrice);
        $this->assertSame(0, $final->deliveryFeeTotal);
    }
}
