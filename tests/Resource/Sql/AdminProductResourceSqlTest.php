<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for the admin Product endpoint —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminProductResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/admin/product`), same body-shape assertions,
 * same AUTHN / AUTHZ / CSRF / 404 / 409 branches. The only difference
 * is the storage binding (ProductQueryInterface → SqlProductQuery,
 * ProductCommandInterface → SqlProductCommand) layered via the base
 * class's sqlOverrideModule; persistence is against the real
 * dtb_product + dtb_product_class tables.
 *
 * The Fake-backed sibling reads the `var/fake/products.json` seed
 * (admin-active-001 etc.); this SQL sibling seeds the equivalent rows
 * through the fixture trait in {@see setUp} so the two suites start
 * from the same client-observable baseline.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but the
 * Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior.
 */
final class AdminProductResourceSqlTest extends AbstractResourceSqlTestCase
{
    use SqlProductSeedTrait;

    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** @var non-empty-string|null */
    private string|null $currentAdminId = self::TEST_ADMIN_ID;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedProducts();
    }

    protected function extraOverride(): AbstractModule|null
    {
        $adminId = $this->currentAdminId;

        return new class ($adminId) extends AbstractModule {
            /** @param non-empty-string|null $adminId */
            public function __construct(private readonly string|null $adminId)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)
                    ->toInstance(new FakeAdminSession($this->adminId));
            }
        };
    }

    /**
     * Swap the admin session adminId and rebuild the Resource client so
     * the new binding takes effect — same shape as the Fake-backed
     * sibling's `rebindAdminSession`.
     *
     * @param non-empty-string|null $adminId
     */
    private function rebindAdminSession(string|null $adminId): void
    {
        $this->currentAdminId = $adminId;
        $this->resource = $this->buildResource();
    }

    // ----------- onGet (admin variant goProduct) -----------

    public function testOnGetReturnsAdminProductDetail(): void
    {
        $ro = $this->resource->get('page://self/admin/product', ['productCode' => 'admin-active-001']);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('admin-active-001', $ro->body['productCode']);
        $this->assertSame('管理画面用 商品A', $ro->body['productName']);
        $this->assertSame(3500, $ro->body['price02']);
        $this->assertSame(1, $ro->body['productStatus']);
        $this->assertArrayHasKey('note', $ro->body);
        $this->assertArrayHasKey('searchWord', $ro->body);
    }

    public function testOnGetUnknownProductReturns404(): void
    {
        $ro = $this->resource->get('page://self/admin/product', ['productCode' => 'does-not-exist']);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnGetWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/product', ['productCode' => 'admin-active-001']);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    // ----------- onPost (doCreateProduct) -----------

    public function testOnPostCreatesProductAndReturns201(): void
    {
        $ro = $this->resource->post('page://self/admin/product', [
            'productCode' => 'wave8-resource-001',
            'productName' => 'Wave 8 Resource',
            'price02' => 2500,
            'stock' => 30,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('wave8-resource-001', $ro->body['productCode']);
        $this->assertSame(1, $ro->body['productStatus']);
        $this->assertArrayHasKey('Location', $ro->headers);
    }

    public function testOnPostDuplicateReturns409(): void
    {
        $ro = $this->resource->post('page://self/admin/product', [
            'productCode' => 'admin-active-001',
            'productName' => 'duplicate',
            'price02' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(409, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/product', [
            'productCode' => 'wave8-no-csrf-001',
            'productName' => 'No CSRF',
            'price02' => 100,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPostWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/product', [
            'productCode' => 'wave8-noadm-001',
            'productName' => 'No admin',
            'price02' => 100,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }

    // ----------- onPut (doUpdateProduct) -----------

    public function testOnPutPartialUpdateReturns200(): void
    {
        $ro = $this->resource->put('page://self/admin/product', [
            'productCode' => 'admin-active-001',
            'productName' => '更新後',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('更新後', $ro->body['productName']);
        // Price is unchanged.
        $this->assertSame(3500, $ro->body['price02']);
    }

    public function testOnPutUnknownProductReturns404(): void
    {
        $ro = $this->resource->put('page://self/admin/product', [
            'productCode' => 'does-not-exist',
            'productName' => 'whatever',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnPutWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->put('page://self/admin/product', [
            'productCode' => 'admin-active-001',
            'productName' => 'no admin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    // ----------- onDelete (doDeleteProduct) -----------

    public function testOnDeleteHappyPathReturns200(): void
    {
        $ro = $this->resource->delete('page://self/admin/product', [
            'productCode' => 'admin-active-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('admin-active-001', $ro->body['productCode']);
        $this->assertFalse($ro->body['alreadyDeleted']);
    }

    public function testOnDeleteIdempotentReplayReturnsAlreadyDeleted(): void
    {
        $this->resource->delete('page://self/admin/product', [
            'productCode' => 'admin-active-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $ro = $this->resource->delete('page://self/admin/product', [
            'productCode' => 'admin-active-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertTrue($ro->body['alreadyDeleted']);
    }

    public function testOnDeleteUnknownReturns404(): void
    {
        $ro = $this->resource->delete('page://self/admin/product', [
            'productCode' => 'does-not-exist',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnDeleteMissingCsrfReturns403(): void
    {
        $ro = $this->resource->delete('page://self/admin/product', [
            'productCode' => 'admin-active-001',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }
}
