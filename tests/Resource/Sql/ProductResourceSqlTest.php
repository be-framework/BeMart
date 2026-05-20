<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;

/**
 * SQL-backed hypermedia coverage for the storefront product endpoint —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\ProductResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/product`), same body-shape assertions. The
 * customer-side {@see \MyVendor\BeMart\Be\Final\ProductFetched} resolves
 * the catalog row through ProductQueryInterface → SqlProductQuery
 * (layered via the base class's sqlOverrideModule), so the storefront
 * goProduct flow exercises the SQL backing exactly as the admin
 * variant does. No admin session is needed — the storefront detail
 * page has no AUTHZ.
 *
 * The `testSemanticLogIsWrittenAfterRequest` case from the Fake-backed
 * sibling is intentionally NOT mirrored: it asserts a side-effect of
 * the dev logging harness, not the storage contract.
 */
final class ProductResourceSqlTest extends AbstractResourceSqlTestCase
{
    use SqlProductSeedTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedProducts();
    }

    public function testOnGetReturnsProductBody(): void
    {
        $ro = $this->resource->get('page://self/product', ['productCode' => 'sample-001']);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('sample-001', $ro->body['productCode']);
        $this->assertSame('サンプル商品 A', $ro->body['productName']);
        $this->assertSame(1200, $ro->body['price02']);
        $this->assertSame(50, $ro->body['stock']);
    }

    public function testOnGetMissingProductReturns404(): void
    {
        $ro = $this->resource->get('page://self/product', ['productCode' => 'missing-xyz']);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertSame('missing-xyz', $ro->body['productCode']);
    }
}
