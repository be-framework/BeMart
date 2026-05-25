<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use Ray\Di\AbstractModule;

use function array_column;

/**
 * SQL-backed hypermedia coverage for the admin product-list endpoint —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminProductListResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/admin/product-list`), same body-shape
 * assertions, same AUTHZ branch. ProductQueryInterface → SqlProductQuery
 * is layered via the base class's sqlOverrideModule; the five canonical
 * products are seeded via {@see SqlProductSeedTrait} so the SQL side
 * starts from the same baseline as the Fake-backed sibling.
 */
final class AdminProductListResourceSqlTest extends AbstractResourceSqlTestCase
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
                $this->bind(AdminSessionInterface::class)
                    ->toInstance(new FakeAdminSession($this->adminId));
            }
        };
    }

    /** @param non-empty-string|null $adminId */
    private function rebindAdminSession(string|null $adminId): void
    {
        $this->currentAdminId = $adminId;
        $this->resource = $this->buildResource();
    }

    public function testOnGetReturnsAdminProductList(): void
    {
        $ro = $this->resource->get('page://self/admin/product-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(5, $ro->body['count']);

        $codes = array_column($ro->body['products'], 'productCode');
        $this->assertContains('admin-active-001', $codes);
        $this->assertContains('admin-withdrawn-001', $codes);
    }

    public function testOnGetWithNameFilterNarrowsResults(): void
    {
        $ro = $this->resource->get('page://self/admin/product-list', [
            'nameKeyword' => '管理画面用',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(3, $ro->body['count']);
        $this->assertSame('管理画面用', $ro->body['filters']['nameKeyword']);
    }

    public function testOnGetWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/product-list');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
