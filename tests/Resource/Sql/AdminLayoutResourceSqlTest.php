<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for the admin Layout endpoints —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminLayoutResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URIs (`page://self/admin/layout/layout-list`,
 * `page://self/admin/layout/layout`), same body-shape assertions, same
 * AUTHN / CSRF / 404 branches. The only differences are:
 *
 *  - the storage binding (LayoutStorageInterface → SqlLayoutStorage) is
 *    layered via the base class's sqlOverrideModule; persistence is
 *    against the real dtb_layout table.
 *
 *  - Layout has NO create affordance (the interface is list / getById /
 *    put only — `goLayoutList` + `doUpdateLayout` in ALPS), so there is
 *    no resource-layer POST to seed rows with. The Fake-backed sibling
 *    relies on the JSON layout corpus' two stock seed rows (lo-pc-default /
 *    lo-sp-default) being present on construction; the SQL table is
 *    empty on each test (the per-test transaction rolls back), so this
 *    test seeds the two equivalent rows directly via the insertLayout
 *    fixture helper before each assertion. The seeded ids are numeric
 *    autoinc strings, not the `lo-` prefixed Fake handles — the
 *    update / 404 cases reference the seeded id.
 *
 *  - `nonexistent` is non-numeric — SqlLayoutStorage::getById surfaces
 *    it as a miss, so the LayoutUpdated Final raises its normal 404,
 *    identical to the Fake-backed sibling's `nonexistent` case.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but the
 * Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — a contract change masquerading as a
 * storage change.
 */
final class AdminLayoutResourceSqlTest extends AbstractResourceSqlTestCase
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

    /**
     * Seed the two EC-CUBE stock layouts directly into dtb_layout —
     * the SQL analogue of the JSON layout corpus' PC + Mobile seed pair.
     * Layout has no create affordance so this cannot go through the
     * resource layer; the fixture INSERT writes the rows the same
     * transaction every subsequent assertion reads.
     *
     * Returns the server-generated layoutId of the PC layout (the row
     * the update / 404 cases target — the SQL analogue of the Fake's
     * SEED_PC_LAYOUT_ID).
     */
    private function seedStockLayouts(): int
    {
        $this->seedDeviceTypes();
        $pcId = $this->insertLayout(['layout_name' => 'PC標準', 'device_type_id' => 10]);
        $this->insertLayout(['layout_name' => 'スマホ標準', 'device_type_id' => 2]);

        return $pcId;
    }

    public function testListIncludesSeed(): void
    {
        $this->seedStockLayouts();

        $ro = $this->resource->get('page://self/admin/layout/layout-list');
        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->seedStockLayouts();
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/layout/layout-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testUpdateMerges(): void
    {
        $pcId = $this->seedStockLayouts();

        $ro = $this->resource->put('page://self/admin/layout/layout', [
            'layoutId' => (string) $pcId,
            'layoutName' => 'PC Refreshed',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('PC Refreshed', $ro->body['layoutName']);
    }

    public function testUpdateUnknownReturns404(): void
    {
        $this->seedStockLayouts();

        // `nonexistent` is non-numeric — SqlLayoutStorage::getById
        // surfaces it as a miss, so the LayoutUpdated Final raises its
        // normal 404, identical to the Fake-backed sibling.
        $ro = $this->resource->put('page://self/admin/layout/layout', [
            'layoutId' => 'nonexistent',
            'layoutName' => 'X',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testUpdateRejectsAnonymousAdmin(): void
    {
        $pcId = $this->seedStockLayouts();
        $this->rebindAdminSession(null);

        $ro = $this->resource->put('page://self/admin/layout/layout', [
            'layoutId' => (string) $pcId,
            'layoutName' => 'X',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
