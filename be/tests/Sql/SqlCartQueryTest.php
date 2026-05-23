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

        $query = $this->sql(SqlCartQuery::class);
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

        $query = $this->sql(SqlCartQuery::class);
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

        $query = $this->sql(SqlCartQuery::class);
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

        $query = $this->sql(SqlCartQuery::class);
        $this->assertSame([], $query->bySessionPrefix('ghost-session'));
    }

    public function testParsesMultiDigitSaleTypeIdFromCartKey(): void
    {
        // Suffix may legitimately be multi-digit (saleTypeId can be 10+
        // in custom EC-CUBE installs). Prefix here also contains
        // underscores — parser must split on the LAST `_`.
        $this->insertCart(['cart_key' => 'sess_abc_xyz_127']);

        $query = $this->sql(SqlCartQuery::class);
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

        $query = $this->sql(SqlCartQuery::class);
        $cart = $query->byCartKey('ordering_1');

        $this->assertNotNull($cart);
        $codes = array_map(
            static fn ($item) => $item->productCode,
            $cart->items,
        );
        $this->assertSame(['ORD-C', 'ORD-A', 'ORD-B'], $codes);
    }

    public function testItemsCarryJoinedDisplayFields(): void
    {
        // A product with a name, a main image (lowest sort_no wins) and
        // a two-axis variation — the cart-row display fields the
        // re-derived ALPS CartItem descriptor composes.
        $productId = $this->insertProduct([
            'name' => 'Display Tee',
            'product_code' => 'DISP-TEE',
        ]);
        $this->insertProductImage($productId, ['file_name' => 'late.jpg', 'sort_no' => 9]);
        $this->insertProductImage($productId, ['file_name' => 'hero.jpg', 'sort_no' => 2]);

        $colour = $this->insertClassName(['name' => 'Colour']);
        $size = $this->insertClassName(['name' => 'Size']);
        $red = $this->insertClassCategory(['class_name_id' => $colour, 'name' => 'Red']);
        $large = $this->insertClassCategory(['class_name_id' => $size, 'name' => 'L']);
        $sku = $this->insertProductClassVariation($productId, [
            'product_code' => 'DISP-TEE-RED-L',
            'class_category_id1' => $red,
            'class_category_id2' => $large,
            'price02' => 2500,
        ]);

        $cart = $this->insertCart(['cart_key' => 'display_1']);
        // The cart item references the SPECIFIC variation SKU.
        $this->insertCartItem($cart['id'], $sku, ['price' => 2500, 'quantity' => 4]);

        $query = $this->sql(SqlCartQuery::class);
        $result = $query->byCartKey('display_1');

        $this->assertNotNull($result);
        $this->assertCount(1, $result->items);
        $item = $result->items[0];
        $this->assertSame('DISP-TEE-RED-L', $item->productCode);
        $this->assertSame(4, $item->quantity);
        $this->assertSame(2500, $item->price);
        $this->assertSame($sku, $item->productClassId);
        $this->assertSame($productId, $item->productId);
        $this->assertSame('Display Tee', $item->productName);
        // The image join picks the lowest sort_no.
        $this->assertSame('hero.jpg', $item->mainImage);
        // Both variation axes resolved.
        $this->assertSame('Red', $item->classCategoryName1);
        $this->assertSame('Colour', $item->className1);
        $this->assertSame('L', $item->classCategoryName2);
        $this->assertSame('Size', $item->className2);
    }

    public function testItemsYieldNullDisplayFieldsWhenImageAndVariationAbsent(): void
    {
        // A plain product: default class, no image, no variation.
        $productId = $this->insertProduct([
            'name' => 'Plain Mug',
            'product_code' => 'PLAIN-MUG',
        ]);
        $cart = $this->insertCart(['cart_key' => 'plain_1']);
        $this->insertCartItem(
            $cart['id'],
            $this->defaultProductClassId($productId),
            ['price' => 600, 'quantity' => 1],
        );

        $query = $this->sql(SqlCartQuery::class);
        $result = $query->byCartKey('plain_1');

        $this->assertNotNull($result);
        $item = $result->items[0];
        $this->assertSame('Plain Mug', $item->productName);
        $this->assertSame($productId, $item->productId);
        // No dtb_product_image rows — the sub-select yields null.
        $this->assertNull($item->mainImage);
        // Default class: class_category_id1/2 both NULL — the LEFT
        // JOINs onward to dtb_class_category / dtb_class_name yield null.
        $this->assertNull($item->classCategoryName1);
        $this->assertNull($item->className1);
        $this->assertNull($item->classCategoryName2);
        $this->assertNull($item->className2);
    }
}
