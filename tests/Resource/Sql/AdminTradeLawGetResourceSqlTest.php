<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for the admin TradeLaw GET endpoint —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminTradeLawGetResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/admin/trade-law`), same body-shape assertions,
 * same AUTHZ branch. The only differences:
 *
 *  - the storage binding (TradeLawStorageInterface → SqlTradeLawStorage)
 *    is layered via the base class's sqlOverrideModule, and the storage
 *    is resolved through the injector (not the Fake constructor).
 *
 *  - `dtb_tradelaw` is empty on each test (structure-only schema dump),
 *    so {@see SqlTradeLawStorage::get} returns its installer-default
 *    body. That default is intentionally identical to
 *    TradeLawStorageInterface's constructor seed (contains 株式会社EC-CUBE),
 *    so the assertion shape is identical to the Fake-backed sibling.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but
 * the Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — that's a contract change masquerading
 * as a storage change.
 */
final class AdminTradeLawGetResourceSqlTest extends AbstractResourceSqlTestCase
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
                $this->bind(AdminSession::class)
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

    public function testOnGetReturnsSeedBody(): void
    {
        $ro = $this->resource->get('page://self/admin/trade-law');

        $this->assertSame(Code::OK, $ro->code);

        // dtb_tradelaw is empty in the structure-only dump — the
        // SqlTradeLawStorage::get fall-through returns the same
        // installer-default body TradeLawStorageInterface's constructor
        // encodes, so we observe the same body shape as the Fake-backed
        // sibling on a first read.
        $this->assertStringContainsString('株式会社EC-CUBE', $ro->body['tradeLawBody']);
        // changed flag (write-only field) MUST NOT leak into the read body.
        $this->assertArrayNotHasKey('changed', $ro->body);
    }

    public function testOnGetReturnsSeededCarrierRow(): void
    {
        // Seed the dtb_tradelaw carrier row id=1 directly so the GET
        // exercises the hydrate path (not the installer-default
        // fall-through) end-to-end through the Becoming chain.
        $this->insertTradeLaw([
            'description' => "販売業者: 既存会社\n所在地: 京都市",
        ]);
        // Rebuild so the resource picks up a fresh storage read.
        $this->resource = $this->buildResource();

        $ro = $this->resource->get('page://self/admin/trade-law');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(
            "販売業者: 既存会社\n所在地: 京都市",
            $ro->body['tradeLawBody'],
        );
    }

    public function testOnGetWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/trade-law');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
