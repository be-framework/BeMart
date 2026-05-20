<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for the admin product-copy endpoint —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminProductCopyResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/admin/product-copy`), same body-shape
 * assertions, same AUTHZ / CSRF / 404 / 409 branches. The copy drives
 * the full Becoming chain → SqlProductCommand::copy, which INSERTs both
 * a fresh dtb_product header and its default dtb_product_class row for
 * the clone.
 */
final class AdminProductCopyResourceSqlTest extends AbstractResourceSqlTestCase
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

    public function testOnPostHappyPathReturns201(): void
    {
        $ro = $this->resource->post('page://self/admin/product-copy', [
            'productCode' => 'admin-active-001',
            'newProductCode' => 'admin-active-001.copy',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('admin-active-001.copy', $ro->body['newProductCode']);
        $this->assertStringStartsWith('(コピー) ', $ro->body['newProductName']);
        $this->assertArrayHasKey('Location', $ro->headers);
    }

    public function testOnPostUnknownSourceReturns404(): void
    {
        $ro = $this->resource->post('page://self/admin/product-copy', [
            'productCode' => 'does-not-exist',
            'newProductCode' => 'new-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnPostCollidingTargetReturns409(): void
    {
        $ro = $this->resource->post('page://self/admin/product-copy', [
            'productCode' => 'admin-active-001',
            'newProductCode' => 'sample-001',  // already exists
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(409, $ro->code);
    }

    public function testOnPostWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/product-copy', [
            'productCode' => 'admin-active-001',
            'newProductCode' => 'foo',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/product-copy', [
            'productCode' => 'admin-active-001',
            'newProductCode' => 'foo',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }
}
