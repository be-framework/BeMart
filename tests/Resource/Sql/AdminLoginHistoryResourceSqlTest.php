<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for the admin login-history endpoint —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminLoginHistoryResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URI (`page://self/admin/login-history`), same body-shape
 * assertions, same AUTHZ branch. The only differences are:
 *
 *  - the storage binding (LoginHistoryStorageInterface →
 *    SqlLoginHistoryStorage) is layered via the base class's
 *    sqlOverrideModule; persistence is against the real
 *    dtb_login_history table.
 *
 *  - the Fake-backed sibling leans on FakeLoginHistoryStorage seeding
 *    four sample attempts in its constructor. dtb_login_history is
 *    empty on each test, and the LoginHistory Resource exposes a single
 *    `goLoginHistoryList` affordance — there is NO append / POST
 *    affordance on the resource layer (append() lives on the storage
 *    interface but the Wave 8 Final does not call it). So the SQL
 *    sibling seeds the four equivalent rows directly via the
 *    insertLoginHistory fixture, exactly as AdminTemplateResourceSqlTest
 *    seeds templates the resource layer cannot POST.
 *
 *  - mtb_login_history_status (the NOT NULL FK target) is empty in the
 *    structure-only dump, so setUp seeds it via seedLoginHistoryStatus
 *    before any row is inserted.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but the
 * Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior.
 */
final class AdminLoginHistoryResourceSqlTest extends AbstractResourceSqlTestCase
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

    /**
     * Seed the four sample login attempts that FakeLoginHistoryStorage
     * carries in its constructor — directly via the fixture, since the
     * resource layer cannot append an audit row.
     */
    private function seedSampleAttempts(): void
    {
        $this->seedLoginHistoryStatus();
        $this->insertLoginHistory([
            'user_name' => 'test-admin',
            'login_history_status_id' => 1,
            'client_ip' => '192.0.2.10',
            'create_date' => '2026-05-19 09:12:34',
        ]);
        $this->insertLoginHistory([
            'user_name' => 'test-admin',
            'login_history_status_id' => 0,
            'client_ip' => '203.0.113.45',
            'create_date' => '2026-05-18 22:08:01',
        ]);
        $this->insertLoginHistory([
            'user_name' => 'shop-owner',
            'login_history_status_id' => 1,
            'client_ip' => '198.51.100.7',
            'create_date' => '2026-05-18 18:55:12',
        ]);
        $this->insertLoginHistory([
            'user_name' => 'unknown-user',
            'login_history_status_id' => 0,
            'client_ip' => '203.0.113.99',
            'create_date' => '2026-05-18 08:00:00',
        ]);
    }

    public function testOnGetReturnsLoginHistory(): void
    {
        // FakeLoginHistoryStorage seeds four attempts in its
        // constructor; dtb_login_history is empty on each test, so seed
        // the equivalent rows first. The assertion shape matches the
        // Fake-backed sibling.
        $this->seedSampleAttempts();

        $ro = $this->resource->get('page://self/admin/login-history');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(4, $ro->body['count']);
        $first = $ro->body['entries'][0];
        $this->assertArrayHasKey('timestamp', $first);
        $this->assertArrayHasKey('loginId', $first);
        $this->assertArrayHasKey('success', $first);
        $this->assertArrayHasKey('clientIp', $first);
    }

    public function testOnGetAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/login-history');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
