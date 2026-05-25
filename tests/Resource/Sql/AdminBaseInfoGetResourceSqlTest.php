<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for the admin BaseInfo GET endpoint —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminBaseInfoGetResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/admin/base-info`), same body-shape assertions,
 * same AUTHZ branch. The only differences:
 *
 *  - the storage binding (BaseInfoStorageInterface → SqlBaseInfoStorage)
 *    is layered via the base class's sqlOverrideModule, and the storage
 *    is resolved through the injector (not the Fake constructor).
 *
 *  - dtb_base_info is empty on each test (structure-only schema dump),
 *    so {@see SqlBaseInfoStorage::get} returns its installer-default
 *    Entity. The defaults are intentionally identical to
 *    FakeBaseInfoStorage's constructor seeds, so the assertion shape
 *    (compare body fields to whatever the storage's `get()` returns)
 *    is identical to the Fake-backed sibling.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but
 * the Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — that's a contract change masquerading
 * as a storage change.
 */
final class AdminBaseInfoGetResourceSqlTest extends AbstractResourceSqlTestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** @var non-empty-string|null */
    private string|null $currentAdminId = self::TEST_ADMIN_ID;

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

    /**
     * Swap the admin session adminId and rebuild the Resource client
     * so the new binding takes effect — same shape as the Fake-backed
     * sibling's `rebindAdminSession`.
     *
     * @param non-empty-string|null $adminId
     */
    private function rebindAdminSession(string|null $adminId): void
    {
        $this->currentAdminId = $adminId;
        $this->resource = $this->buildResource();
    }

    public function testOnGetReturnsSeedBaseInfo(): void
    {
        $ro = $this->resource->get('page://self/admin/base-info');

        $this->assertSame(Code::OK, $ro->code);

        // dtb_base_info is empty in the structure-only dump — the
        // SqlBaseInfoStorage::get fall-through returns the same
        // installer defaults FakeBaseInfoStorage's constructor encodes,
        // so we can assert against the SAME constants the Fake-backed
        // sibling sees via `$this->storage->get()`. The two suites
        // therefore observe the same body shape on a first read.
        $this->assertSame('EC-CUBE SHOP', $ro->body['shopName']);
        $this->assertSame('株式会社EC-CUBE', $ro->body['companyName']);
        $this->assertSame(27, $ro->body['pref']);
        $this->assertSame('ようこそ、EC-CUBE SHOP へ。', $ro->body['shopMessage']);
        // changed flag (write-only field) MUST NOT leak into the read body.
        $this->assertArrayNotHasKey('changed', $ro->body);
    }

    public function testOnGetWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/base-info');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
