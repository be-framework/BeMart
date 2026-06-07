<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for doUpdateAuthorityRole — mirror of
 * {@see \MyVendor\BeMart\Tests\Resource\AdminAuthorityRoleResourceTest}
 * (Admin auth Phase B, G-23 storage migration contract).
 *
 * Same URI (`page://self/admin/authority-role`), same body shape,
 * same privilege-escalation guard. The differences from the
 * Fake-backed sibling:
 *
 *  - the storage bindings (AdminQueryInterface → SqlAdminQuery,
 *    AdminCommandInterface → SqlAdminCommand) are layered via the
 *    base class's sqlOverrideModule.
 *
 *  - {@see \MyVendor\BeMart\Be\Final\AuthorityRoleUpdated} resolves
 *    the CALLER via `findById(session adminId)` to read its
 *    authority for the strict-higher-privilege check. SqlAdminQuery
 *    rejects non-numeric ids, so the AdminSession MUST carry the
 *    numeric dtb_member.id of the seeded caller row (the Fake test
 *    can hard-code `ad000…01` / `…02` because the Fake findById
 *    matches that hex). This is the load-bearing reason the SQL
 *    sibling cannot reuse the Fake's hex constants.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings — the privilege-escalation
 * guard is exactly the kind of AUTHZ ladder a DI-bypassing test
 * would fail to validate.
 */
final class AdminAuthorityRoleResourceSqlTest extends AbstractResourceSqlTestCase
{
    /** @var non-empty-string numeric id of the system admin (authority 0) */
    private string $systemAdminId;

    /** @var non-empty-string numeric id of a shop owner (authority 1) */
    private string $shopOwnerId;

    /** @var non-empty-string|null currently-bound session admin id */
    private string|null $currentAdminId = null;

    protected function setUp(): void
    {
        parent::setUp();

        // mtb_work / mtb_authority are empty in the structure-only
        // dump — seed the EC-CUBE canonical rows so dtb_member FKs hold.
        $this->seedAdminMasters();

        $this->systemAdminId = (string) $this->insertAdmin([
            'loginId' => 'test-admin',
            'name' => 'テスト管理者',
            'authority_id' => 0, // system admin — highest privilege
        ]);
        $this->shopOwnerId = (string) $this->insertAdmin([
            'loginId' => 'shop-owner',
            'name' => '店舗オーナー',
            'authority_id' => 1, // shop owner
        ]);
        $this->insertAdmin([
            'loginId' => 'deputy',
            'name' => '副管理者',
            'authority_id' => 1,
        ]);
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
                $this->bind(AdminSession::class)
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

    public function testSystemAdminCanPromoteShopOwner(): void
    {
        $this->rebindAdminSession($this->systemAdminId);

        $ro = $this->resource->post('page://self/admin/authority-role', [
            'loginId' => 'shop-owner',
            'authority' => 0,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertTrue($ro->body['changed']);
        $this->assertSame(1, $ro->body['previousAuthority']);
        $this->assertSame(0, $ro->body['authority']);

        // Read-back through goMember confirms the flip persisted.
        $this->rebindAdminSession($this->systemAdminId);
        $next = $this->resource->get('page://self/admin/member', ['loginId' => 'shop-owner']);
        $this->assertSame(0, $next->body['authority']);
    }

    public function testIdempotentReplaySetsChangedFalse(): void
    {
        $this->rebindAdminSession($this->systemAdminId);

        $ro = $this->resource->post('page://self/admin/authority-role', [
            'loginId' => 'shop-owner',
            'authority' => 1, // already this value
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['changed']);
    }

    public function testShopOwnerCannotPromoteSelf(): void
    {
        // THE critical privilege-escalation guard test.
        $this->rebindAdminSession($this->shopOwnerId);

        $ro = $this->resource->post('page://self/admin/authority-role', [
            'loginId' => 'shop-owner',
            'authority' => 0,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('権限', $ro->body['message']);
    }

    public function testShopOwnerCannotFlipPeer(): void
    {
        $this->rebindAdminSession($this->shopOwnerId);

        $ro = $this->resource->post('page://self/admin/authority-role', [
            'loginId' => 'deputy',
            'authority' => 0,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testUnknownTargetReturns404(): void
    {
        $this->rebindAdminSession($this->systemAdminId);

        $ro = $this->resource->post('page://self/admin/authority-role', [
            'loginId' => 'no-such-admin',
            'authority' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testMissingCsrfReturns403(): void
    {
        $this->rebindAdminSession($this->systemAdminId);

        $ro = $this->resource->post('page://self/admin/authority-role', [
            'loginId' => 'shop-owner',
            'authority' => 0,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/authority-role', [
            'loginId' => 'shop-owner',
            'authority' => 0,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
