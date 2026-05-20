<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use Ray\Di\AbstractModule;

use function array_column;

/**
 * SQL-backed hypermedia coverage for the admin member-list endpoint —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminMemberListResourceTest}
 * (Admin auth Phase B, G-23 storage migration contract).
 *
 * Same URI (`page://self/admin/member-list`), same listAll / search
 * projection assertions, same admin-firewall 403 branch. The
 * difference from the Fake-backed sibling: the storage binding
 * (AdminQueryInterface → SqlAdminQuery) is layered via the base
 * class's sqlOverrideModule, so listAll / search run as real SQL
 * (LIMIT / OFFSET / LIKE) against seeded dtb_member rows.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings.
 */
final class AdminMemberListResourceSqlTest extends AbstractResourceSqlTestCase
{
    /** @var non-empty-string|null numeric dtb_member.id of test-admin */
    private string|null $currentAdminId = null;

    protected function setUp(): void
    {
        parent::setUp();

        // mtb_work / mtb_authority are empty in the structure-only
        // dump — seed the EC-CUBE canonical rows so dtb_member FKs hold.
        $this->seedAdminMasters();

        // The same three-admin roster as the Fake fixture.
        $testAdminId = (string) $this->insertAdmin([
            'login_id' => 'test-admin',
            'name' => 'テスト管理者',
            'authority_id' => 0,
        ]);
        $this->insertAdmin(['login_id' => 'shop-owner', 'name' => '店舗オーナー', 'authority_id' => 1]);
        $this->insertAdmin(['login_id' => 'deputy', 'name' => '副管理者', 'authority_id' => 1]);

        $this->rebindAdminSession($testAdminId);
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

    public function testOnGetReturnsMemberList(): void
    {
        $ro = $this->resource->get('page://self/admin/member-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(3, $ro->body['count']);
        $loginIds = array_column($ro->body['members'], 'loginId');
        $this->assertContains('test-admin', $loginIds);

        // Shallow projection — no credentials leak.
        foreach ($ro->body['members'] as $row) {
            $this->assertArrayNotHasKey('passwordHash', $row);
        }
    }

    public function testOnGetWithNameFilterNarrowsResults(): void
    {
        $ro = $this->resource->get('page://self/admin/member-list', [
            'nameKeyword' => '副',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(1, $ro->body['count']);
        $this->assertSame('deputy', $ro->body['members'][0]['loginId']);
    }

    public function testOnGetWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/member-list');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
