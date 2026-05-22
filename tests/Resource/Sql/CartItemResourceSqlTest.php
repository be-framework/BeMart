<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;

/**
 * SQL-backed hypermedia coverage for doAddCartItem (Phase 2b).
 *
 * Mirrors the slice of {@see \MyVendor\BeMart\Tests\Resource\CartItemResourceTest}
 * that touches {@see \MyVendor\BeMart\Be\Reason\Query\ProductClassQueryInterface}
 * — every `page://self/cart/item` POST runs QuantityAdjusted, whose
 * only injected dependency is the ProductClassQuery (rebound to
 * {@see \MyVendor\BeMart\Be\Reason\Query\SqlProductClassQuery} in the
 * parent base class). The PUT / DELETE affordances and the CSRF cases
 * are already covered Fake-side and exercise the cart-write reasons
 * (SqlCartCommand / SqlCartQuery) rather than ProductClassQuery, so they
 * are not re-mirrored here — this sibling is the ProductClassQuery
 * migration contract.
 *
 * The Fake-backed CartItemResourceTest keys on fixed productCodes from
 * var/fake/product_classes.json (`sample-001` price02=1200,
 * `out-of-stock-test-001` stock=0, `single-purchase-rare-coin`
 * sale_limit=1). The SQL sibling seeds dtb_product rows with the same
 * shapes via `insertProduct` and asserts the same client-visible
 * projection (Code + body fields) — Fake green AND SQL green proves the
 * storage swap left the contract untouched (G-23).
 */
final class CartItemResourceSqlTest extends AbstractResourceSqlTestCase
{
    public function testOnPostAddsItemAndReturns201(): void
    {
        $this->seedSaleTypes();
        $this->insertProduct([
            'product_code' => 'sql-pc-001',
            'price02' => 1200,
            'stock' => 50,
            'stock_unlimited' => 0,
            'sale_type_id' => 1,
        ]);

        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sql-pc-001',
            'quantity' => 2,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('sql-pc-001', $ro->body['productCode']);
        $this->assertSame(2, $ro->body['adjustedQuantity']);
        // price02 1200 * 2 = 2400.
        $this->assertSame(2400, $ro->body['totalPrice']);
        $this->assertSame(1200, $ro->body['unitPrice']);
        $this->assertSame('通常販売', $ro->body['saleTypeName']);
        $this->assertSame('/cart', $ro->headers['Location']);
    }

    public function testOnPostMissingProductReturns404(): void
    {
        // No dtb_product_class row for this code — SqlProductClassQuery
        // returns null, QuantityAdjusted throws ProductClassNotFound,
        // the Resource maps it to 404.
        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sql-missing-xyz',
            'quantity' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertSame('sql-missing-xyz', $ro->body['productCode']);
    }

    public function testOnPostOutOfStockReturns409(): void
    {
        // stock = 0, not unlimited → OutOfStockException → 409.
        $this->insertProduct([
            'product_code' => 'sql-pc-oos',
            'stock' => 0,
            'stock_unlimited' => 0,
        ]);

        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sql-pc-oos',
            'quantity' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(409, $ro->code);
    }

    public function testOnPostCapsQuantityToSaleLimit(): void
    {
        // sale_limit = 1: a request for 3 is capped to 1 by
        // QuantityAdjusted reading SqlProductClassQuery's saleLimit.
        // A non-NULL sale_type_id is required: QuantityAdjusted builds
        // cartKey as `{sessionPrefix}_{saleTypeId}` and Semantic\CartKey
        // rejects a `_0` suffix — so the CREATED path always needs a
        // seeded saleType (NULL → saleTypeId 0 → 400).
        $this->seedSaleTypes();
        $this->insertProduct([
            'product_code' => 'sql-pc-rare',
            'price02' => 28000,
            'stock' => 10,
            'stock_unlimited' => 0,
            'sale_limit' => 1,
            'sale_type_id' => 1,
        ]);

        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sql-pc-rare',
            'quantity' => 3,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame(3, $ro->body['requestedQuantity']);
        $this->assertSame(1, $ro->body['adjustedQuantity']);
        $this->assertSame(28000, $ro->body['totalPrice']);
    }

    public function testOnPostCapsQuantityToStock(): void
    {
        // stock = 3, not unlimited: a request for 5 is capped to 3.
        $this->seedSaleTypes();
        $this->insertProduct([
            'product_code' => 'sql-pc-scarce',
            'price02' => 4500,
            'stock' => 3,
            'stock_unlimited' => 0,
            'sale_type_id' => 1,
        ]);

        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sql-pc-scarce',
            'quantity' => 5,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame(5, $ro->body['requestedQuantity']);
        $this->assertSame(3, $ro->body['adjustedQuantity']);
        $this->assertSame(13500, $ro->body['totalPrice']);
    }

    public function testOnPostUnlimitedStockHonoursRequestedQuantity(): void
    {
        // stock_unlimited products carry NULL stock — no cap applies.
        $this->seedSaleTypes();
        $this->insertProduct([
            'product_code' => 'sql-pc-unlimited',
            'price02' => 800,
            'stock' => null,
            'stock_unlimited' => 1,
            'sale_type_id' => 3,
        ]);

        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sql-pc-unlimited',
            'quantity' => 7,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame(7, $ro->body['adjustedQuantity']);
        $this->assertSame(5600, $ro->body['totalPrice']);
    }

    public function testOnPostSkipsNonDefaultVariationRow(): void
    {
        // A productCode that ONLY appears on a non-default variation row
        // (one axis non-NULL) is not resolvable by SqlProductClassQuery
        // — the cart-add folds to a 404 exactly as an unknown code does.
        $productId = $this->insertProduct(['product_code' => 'sql-pc-default']);
        $axisValue = $this->insertClassCategory(['name' => 'Large']);
        $this->insertProductClassVariation($productId, [
            'product_code' => 'sql-pc-variation',
            'class_category_id1' => $axisValue,
        ]);

        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sql-pc-variation',
            'quantity' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertSame('sql-pc-variation', $ro->body['productCode']);
    }

    public function testOnPostInvalidQuantityReturns400(): void
    {
        // Semantic validation rejects quantity 0 before the Becoming
        // chain ever reaches SqlProductClassQuery — same boundary the
        // Fake-backed test asserts.
        $this->insertProduct(['product_code' => 'sql-pc-badqty']);

        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sql-pc-badqty',
            'quantity' => 0,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
        $this->assertNotEmpty($ro->body['message']);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        // CSRF is rejected at the resource boundary, before the Becoming
        // chain (and therefore SqlProductClassQuery) is reached.
        $this->insertProduct(['product_code' => 'sql-pc-csrf']);

        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sql-pc-csrf',
            'quantity' => 1,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }
}
