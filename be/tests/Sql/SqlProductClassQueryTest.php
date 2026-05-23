<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\ProductClassEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlProductClassQuery;

/**
 * Storage-layer coverage for {@see SqlProductClassQuery} (Phase 2b).
 *
 * Per G-23 the client-observable contract lives in the Resource-layer
 * sibling ({@see \MyVendor\BeMart\Tests\Resource\Sql\CartItemResourceSqlTest});
 * the cases below pin the per-method SQL paths in isolation.
 *
 * Surprises this suite locks in:
 *  - `dtb_product_class.product_code` is the lookup key directly — no
 *    surrogate-id indirection (unlike SqlFavoriteStorage's product_id
 *    join). The default-class filter
 *    (`class_category_id1 IS NULL AND class_category_id2 IS NULL`)
 *    collapses a variation product to its single representative row,
 *    and a productCode that ONLY appears on a non-default variation
 *    row is an honest miss.
 *  - `productName` is the header name on `dtb_product`, not on the
 *    class row — hence the INNER JOIN to dtb_product.
 *  - `saleTypeName` is resolved via a LEFT JOIN to the EMPTY
 *    mtb_sale_type master; without seedSaleTypes it coalesces to ''.
 *  - decimal money (`price02` / `delivery_fee`) → int cast (JPY);
 *    nullable `stock` / `sale_limit` keep NULL; `delivery_fee` NULL
 *    coalesces to 0.
 */
final class SqlProductClassQueryTest extends AbstractSqlTestCase
{
    public function testItemReturnsNullWhenProductCodeUnknown(): void
    {
        $query = $this->sql(SqlProductClassQuery::class);
        $this->assertNull($query->item('no-such-code'));
    }

    public function testItemReturnsHydratedEntityForDefaultClass(): void
    {
        $this->seedSaleTypes();
        $this->insertProduct([
            'name' => 'サンプル商品 A',
            'product_code' => 'pc-sql-001',
            'price02' => 1200,
            'stock' => 50,
            'stock_unlimited' => 0,
            'sale_type_id' => 1,
            'delivery_fee' => 0,
        ]);

        $query = $this->sql(SqlProductClassQuery::class);
        $entity = $query->item('pc-sql-001');

        $this->assertInstanceOf(ProductClassEntity::class, $entity);
        $this->assertSame('pc-sql-001', $entity->productCode);
        $this->assertSame('サンプル商品 A', $entity->productName);
        $this->assertSame(50, $entity->stock);
        $this->assertFalse($entity->stockUnlimited);
        $this->assertNull($entity->saleLimit);
        $this->assertSame(1200, $entity->price02);
        $this->assertSame(0, $entity->deliveryFee);
        $this->assertSame('通常販売', $entity->saleTypeName);
        $this->assertSame(1, $entity->saleTypeId);
    }

    public function testItemHydratesUnlimitedStockAsNull(): void
    {
        // stock_unlimited products carry a NULL stock count.
        $this->insertProduct([
            'product_code' => 'pc-unlimited',
            'stock' => null,
            'stock_unlimited' => 1,
        ]);

        $query = $this->sql(SqlProductClassQuery::class);
        $entity = $query->item('pc-unlimited');

        $this->assertInstanceOf(ProductClassEntity::class, $entity);
        $this->assertNull($entity->stock);
        $this->assertTrue($entity->stockUnlimited);
    }

    public function testItemHydratesZeroStock(): void
    {
        // An explicit 0 (out of stock) must survive the int cast — it is
        // distinct from NULL (unlimited).
        $this->insertProduct([
            'product_code' => 'pc-oos',
            'stock' => 0,
            'stock_unlimited' => 0,
        ]);

        $query = $this->sql(SqlProductClassQuery::class);
        $entity = $query->item('pc-oos');

        $this->assertInstanceOf(ProductClassEntity::class, $entity);
        $this->assertSame(0, $entity->stock);
        $this->assertFalse($entity->stockUnlimited);
    }

    public function testItemHydratesSaleLimit(): void
    {
        $this->insertProduct([
            'product_code' => 'pc-limited',
            'sale_limit' => 2,
        ]);

        $query = $this->sql(SqlProductClassQuery::class);
        $entity = $query->item('pc-limited');

        $this->assertInstanceOf(ProductClassEntity::class, $entity);
        $this->assertSame(2, $entity->saleLimit);
    }

    public function testItemCastsDecimalMoneyToInt(): void
    {
        // price02 / delivery_fee are decimal columns — JPY has no minor
        // unit, so the projection must be a plain int.
        $this->insertProduct([
            'product_code' => 'pc-money',
            'price02' => 4500,
            'delivery_fee' => 200,
        ]);

        $query = $this->sql(SqlProductClassQuery::class);
        $entity = $query->item('pc-money');

        $this->assertInstanceOf(ProductClassEntity::class, $entity);
        $this->assertSame(4500, $entity->price02);
        $this->assertSame(200, $entity->deliveryFee);
    }

    public function testItemCoercesNullDeliveryFeeToZero(): void
    {
        // delivery_fee is column-nullable but ProductClassEntity types
        // it non-null int — NULL must coalesce to 0.
        $this->insertProduct([
            'product_code' => 'pc-nofee',
            'delivery_fee' => null,
        ]);

        $query = $this->sql(SqlProductClassQuery::class);
        $entity = $query->item('pc-nofee');

        $this->assertInstanceOf(ProductClassEntity::class, $entity);
        $this->assertSame(0, $entity->deliveryFee);
    }

    public function testItemDefaultsSaleTypeNameToEmptyWhenSaleTypeNull(): void
    {
        // sale_type_id is a nullable FK to mtb_sale_type. The default
        // class row may carry NULL — the LEFT JOIN keeps the row and
        // saleTypeName coalesces to '' (saleTypeId folds to 0). A
        // NON-NULL sale_type_id is never an "empty master" case: the FK
        // FK_1A11D1BAB0524E01 is enforced, so a row with a non-NULL
        // sale_type_id always has a matching master row (unlike
        // dtb_cart, where saleTypeId is derived from the cart_key suffix
        // and SqlCartQuery's empty-master fallback genuinely fires).
        $this->insertProduct([
            'product_code' => 'pc-nullsaletype',
            'sale_type_id' => null,
        ]);

        $query = $this->sql(SqlProductClassQuery::class);
        $entity = $query->item('pc-nullsaletype');

        $this->assertInstanceOf(ProductClassEntity::class, $entity);
        $this->assertSame('', $entity->saleTypeName);
        $this->assertSame(0, $entity->saleTypeId);
    }

    public function testItemResolvesPreorderSaleType(): void
    {
        // saleTypeId drives cart separation — a 予約販売 product must
        // round-trip its non-default saleType.
        $this->seedSaleTypes();
        $this->insertProduct([
            'product_code' => 'pc-preorder',
            'sale_type_id' => 2,
        ]);

        $query = $this->sql(SqlProductClassQuery::class);
        $entity = $query->item('pc-preorder');

        $this->assertInstanceOf(ProductClassEntity::class, $entity);
        $this->assertSame(2, $entity->saleTypeId);
        $this->assertSame('予約販売', $entity->saleTypeName);
    }

    public function testItemSkipsNonDefaultVariationRow(): void
    {
        // A productCode that ONLY appears on a non-default variation row
        // (one axis non-NULL) is NOT resolvable — the query restricts to
        // the default class, mirroring SqlFavoriteStorage / SqlCartCommand.
        $productId = $this->insertProduct(['product_code' => 'pc-default-only']);
        $axisValue = $this->insertClassCategory(['name' => 'Red']);
        $this->insertProductClassVariation($productId, [
            'product_code' => 'pc-variation-red',
            'class_category_id1' => $axisValue,
        ]);

        $query = $this->sql(SqlProductClassQuery::class);

        // The variation-only code does not resolve …
        $this->assertNull($query->item('pc-variation-red'));
        // … but the default class on the same product still does.
        $this->assertInstanceOf(
            ProductClassEntity::class,
            $query->item('pc-default-only'),
        );
    }

    public function testItemPicksDefaultClassWhenProductHasVariations(): void
    {
        // A product with both a default class and variation rows: item()
        // returns the default (representative) row by productCode.
        $productId = $this->insertProduct([
            'product_code' => 'pc-with-variations',
            'price02' => 999,
        ]);
        $axis1 = $this->insertClassCategory(['name' => 'Small']);
        $axis2 = $this->insertClassCategory(['name' => 'Blue']);
        $this->insertProductClassVariation($productId, [
            'product_code' => 'pc-with-variations-sb',
            'class_category_id1' => $axis1,
            'class_category_id2' => $axis2,
            'price02' => 1500,
        ]);

        $query = $this->sql(SqlProductClassQuery::class);
        $entity = $query->item('pc-with-variations');

        $this->assertInstanceOf(ProductClassEntity::class, $entity);
        $this->assertSame('pc-with-variations', $entity->productCode);
        $this->assertSame(999, $entity->price02);
    }
}
