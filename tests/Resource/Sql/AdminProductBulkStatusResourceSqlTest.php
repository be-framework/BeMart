<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for the admin product bulk-status
 * endpoint — mirror of
 * {@see \MyVendor\BeMart\Tests\Resource\AdminProductBulkStatusResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/admin/product-bulk-status`), same body-shape
 * assertions, same AUTHZ / CSRF / 400 branches. The POST drives the
 * full Becoming chain → SqlProductCommand::bulkUpdateStatus, which
 * flips dtb_product.product_status_id for the requested codes and
 * returns the count actually changed.
 */
final class AdminProductBulkStatusResourceSqlTest extends AbstractResourceSqlTestCase
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

    public function testOnPostHappyPathReturns200(): void
    {
        $ro = $this->resource->post('page://self/admin/product-bulk-status', [
            'productCodes' => ['admin-active-001', 'admin-hidden-001'],
            'productStatus' => 3,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['requestedCount']);
        $this->assertSame(2, $ro->body['changedCount']);
    }

    public function testOnPostWithUnknownCodesReportsPartialCount(): void
    {
        $ro = $this->resource->post('page://self/admin/product-bulk-status', [
            'productCodes' => ['admin-active-001', 'does-not-exist'],
            'productStatus' => 2,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['requestedCount']);
        $this->assertSame(1, $ro->body['changedCount']);
    }

    public function testOnPostInvalidStatusReturns400(): void
    {
        $ro = $this->resource->post('page://self/admin/product-bulk-status', [
            'productCodes' => ['admin-active-001'],
            'productStatus' => 99,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/product-bulk-status', [
            'productCodes' => ['admin-active-001'],
            'productStatus' => 2,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPostWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/product-bulk-status', [
            'productCodes' => ['admin-active-001'],
            'productStatus' => 2,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
