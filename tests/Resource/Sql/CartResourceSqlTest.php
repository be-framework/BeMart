<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;

/**
 * SQL-backed hypermedia coverage for goCart (Phase 2a Step 5).
 *
 * Mirrors {@see \MyVendor\BeMart\Tests\Resource\CartResourceTest} but
 * drives the Becoming chain through {@see \MyVendor\BeMart\Be\Reason\Query\SqlCartQuery}
 * (rebound in the parent base class). Carts and items are seeded via
 * `insertCart` + `insertCartItem` SQL fixture helpers.
 *
 * The Cart resource has no AUTHZ — sessionPrefix is the only filter —
 * so coverage here is shape + sort-order + empty-prefix handling.
 */
final class CartResourceSqlTest extends AbstractResourceSqlTestCase
{
    public function testOnGetReturnsCartsForSessionPrefixSortedBySaleType(): void
    {
        // Two products per cart.
        $a1 = $this->insertProduct(['product_code' => 'SQL-CART-A1', 'price02' => 100]);
        $a2 = $this->insertProduct(['product_code' => 'SQL-CART-A2', 'price02' => 200]);
        $b1 = $this->insertProduct(['product_code' => 'SQL-CART-B1', 'price02' => 300]);

        // Cart 1: cart_key suffix _1 (saleTypeId = 1), totals 500/200.
        $cart1 = $this->insertCart([
            'cart_key' => 'sess-1_1',
            'pre_order_id' => 'pre-sess-1-1',
            'total_price' => 500,
            'delivery_fee_total' => 200,
        ]);
        $this->insertCartItem(
            $cart1['id'],
            $this->defaultProductClassId($a1),
            ['price' => 100, 'quantity' => 1],
        );
        $this->insertCartItem(
            $cart1['id'],
            $this->defaultProductClassId($a2),
            ['price' => 200, 'quantity' => 2],
        );

        // Cart 2: cart_key suffix _2 (saleTypeId = 2), totals 300/0.
        $cart2 = $this->insertCart([
            'cart_key' => 'sess-1_2',
            'pre_order_id' => 'pre-sess-1-2',
            'total_price' => 300,
            'delivery_fee_total' => 0,
        ]);
        $this->insertCartItem(
            $cart2['id'],
            $this->defaultProductClassId($b1),
            ['price' => 300, 'quantity' => 1],
        );

        // Unrelated cart that MUST NOT show up under this session prefix.
        $this->insertCart(['cart_key' => 'somebody-else_1']);

        $ro = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'sess-1',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['cartCount']);
        // Aggregated totals across both carts.
        $this->assertSame(800, $ro->body['totalPrice']);
        $this->assertSame(200, $ro->body['deliveryFeeTotal']);

        // Sorted by saleTypeId ascending (cart1 first, cart2 second).
        $this->assertCount(2, $ro->body['carts']);
        $this->assertSame('sess-1_1', $ro->body['carts'][0]['cartKey']);
        $this->assertSame(1, $ro->body['carts'][0]['saleTypeId']);
        $this->assertSame('sess-1_2', $ro->body['carts'][1]['cartKey']);
        $this->assertSame(2, $ro->body['carts'][1]['saleTypeId']);

        // Items hydrated through the JOIN to dtb_product_class.
        $cart1Codes = array_map(
            static fn (array $item): string => $item['productCode'],
            $ro->body['carts'][0]['items'],
        );
        sort($cart1Codes);
        $this->assertSame(['SQL-CART-A1', 'SQL-CART-A2'], $cart1Codes);
    }

    public function testOnGetReturnsEmptyForUnknownPrefix(): void
    {
        // Seed an unrelated cart so the prefix filter is doing real work.
        $this->insertCart(['cart_key' => 'other-session_1']);

        $ro = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'no-such-session',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(0, $ro->body['cartCount']);
        $this->assertSame([], $ro->body['carts']);
        $this->assertSame(0, $ro->body['totalPrice']);
    }
}
