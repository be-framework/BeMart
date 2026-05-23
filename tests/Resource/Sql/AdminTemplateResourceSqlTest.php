<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for the admin Template endpoint —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminTemplateResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/admin/template/template-list`), same body-shape
 * assertions, same AUTHN branch. The only differences are:
 *
 *  - the storage binding (TemplateStorageInterface → SqlTemplateStorage)
 *    is layered via the base class's sqlOverrideModule; the list is read
 *    from the real dtb_template table.
 *
 *  - Template has NO create affordance (the interface is `list()` only —
 *    `goTemplateList` in ALPS), so there is no resource-layer POST to
 *    seed rows with. The Fake-backed sibling relies on FakeTemplateStorage's
 *    two stock seed rows (tp-default-pc / tp-default-sp) being present on
 *    construction; the SQL table is empty on each test (the per-test
 *    transaction rolls back), so this test seeds the two equivalent rows
 *    directly via the insertTemplate fixture helper before each assertion.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but the
 * Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — a contract change masquerading as a
 * storage change.
 */
final class AdminTemplateResourceSqlTest extends AbstractResourceSqlTestCase
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
     * Seed the two EC-CUBE stock design templates directly into
     * dtb_template — the SQL analogue of FakeTemplateStorage's PC +
     * Mobile seed pair. Template has no create affordance so this
     * cannot go through the resource layer; the fixture INSERT writes
     * the rows the same transaction every subsequent assertion reads.
     */
    private function seedStockTemplates(): void
    {
        $this->seedDeviceTypes();
        $this->insertTemplate(['template_name' => 'デフォルト (PC)', 'device_type_id' => 10]);
        $this->insertTemplate(['template_name' => 'デフォルト (スマホ)', 'device_type_id' => 2]);
    }

    public function testListReturnsSeed(): void
    {
        $this->seedStockTemplates();

        $ro = $this->resource->get('page://self/admin/template/template-list');
        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(2, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->seedStockTemplates();
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/template/template-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
