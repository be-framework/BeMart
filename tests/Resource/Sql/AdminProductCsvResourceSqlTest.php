<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for the admin product-csv export
 * endpoint — mirror of
 * {@see \MyVendor\BeMart\Tests\Resource\AdminProductCsvResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/admin/product-csv`), same body-shape
 * assertions, same AUTHZ branch. ProductQueryInterface → SqlProductQuery
 * is layered via the base class's sqlOverrideModule; the five canonical
 * products are seeded via {@see SqlProductSeedTrait}.
 */
final class AdminProductCsvResourceSqlTest extends AbstractResourceSqlTestCase
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

    public function testOnGetReturnsCsvAndAttachmentHeader(): void
    {
        $ro = $this->resource->get('page://self/admin/product-csv');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(5, $ro->body['count']);
        $this->assertStringContainsString('productCode,productName', $ro->body['csv']);
        $this->assertStringContainsString('sample-001', $ro->body['csv']);
        $this->assertSame('text/csv; charset=UTF-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('products.csv', $ro->headers['Content-Disposition']);
    }

    public function testOnGetWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/product-csv');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
