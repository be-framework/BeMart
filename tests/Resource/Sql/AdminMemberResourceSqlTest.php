<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for the admin Member CRUD endpoint —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminMemberResourceTest}
 * (Admin auth Phase B, G-23 storage migration contract).
 *
 * Same URI (`page://self/admin/member`), same GET / POST / PUT /
 * DELETE verbs, same body-shape + AUTHZ + CSRF assertions. The
 * differences from the Fake-backed sibling:
 *
 *  - the storage bindings (AdminQueryInterface → SqlAdminQuery,
 *    AdminCommandInterface → SqlAdminCommand, AdminIdQueryInterface
 *    → direct MediaQuery admin id proxy) are layered via the base class's
 *    sqlOverrideModule; CRUD runs against real dtb_member rows.
 *
 *  - the three seed admins (test-admin / shop-owner / deputy) are
 *    inserted via {@see insertAdmin}, and their server-assigned
 *    numeric dtb_member.id values drive the AdminSession binding —
 *    {@see \MyVendor\BeMart\Be\Final\MemberDeleted} compares the
 *    session adminId against the target's adminId for the
 *    self-delete guard, so the session id MUST be the real row id
 *    (the Fake test can hard-code `ad000…01` because the Fake's
 *    findById matches that hex; SqlAdminQuery::findById rejects
 *    non-numeric ids).
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings.
 */
final class AdminMemberResourceSqlTest extends AbstractResourceSqlTestCase
{
    /** @var non-empty-string|null numeric dtb_member.id of test-admin */
    private string|null $currentAdminId = null;

    /** @var non-empty-string numeric dtb_member.id of test-admin */
    private string $testAdminId;

    /** @var non-empty-string numeric dtb_member.id of shop-owner */
    private string $shopOwnerId;

    protected function setUp(): void
    {
        parent::setUp();

        // mtb_work / mtb_authority are empty in the structure-only
        // dump — seed the EC-CUBE canonical rows so dtb_member FKs hold.
        $this->seedAdminMasters();

        // Three seed admins matching the Fake fixture's roster. The
        // returned ids are the numeric dtb_member.id PKs.
        $this->testAdminId = (string) $this->insertAdmin([
            'loginId' => 'test-admin',
            'name' => 'テスト管理者',
            'authority_id' => 0, // system admin
        ]);
        $this->shopOwnerId = (string) $this->insertAdmin([
            'loginId' => 'shop-owner',
            'name' => '店舗オーナー',
            'authority_id' => 1, // shop owner
            'sort_no' => 2,
        ]);
        $this->insertAdmin([
            'loginId' => 'deputy',
            'name' => '副管理者',
            'authority_id' => 1,
        ]);

        $this->rebindAdminSession($this->testAdminId);
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

    public function testOnGetHappyPathReturnsAdminDetail(): void
    {
        $ro = $this->resource->get('page://self/admin/member', ['loginId' => 'shop-owner']);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('shop-owner', $ro->body['loginId']);
        $this->assertSame('店舗オーナー', $ro->body['name']);
        $this->assertSame(1, $ro->body['authority']);
        $this->assertArrayNotHasKey('passwordHash', $ro->body);
    }

    public function testOnGetUnknownLoginIdReturns404(): void
    {
        $ro = $this->resource->get('page://self/admin/member', ['loginId' => 'no-such-admin']);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnGetAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/member', ['loginId' => 'shop-owner']);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnPostCreatesNewAdmin(): void
    {
        $ro = $this->resource->post('page://self/admin/member', [
            'loginId' => 'fresh-admin',
            'password' => 'fresh-admin-password-2026',
            'name' => '新人管理者',
            'authority' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('fresh-admin', $ro->body['loginId']);
        $this->assertSame(1, $ro->body['authority']);
        $this->assertSame(1, $ro->body['work']);
        $this->assertArrayNotHasKey('mailAddress', $ro->body);
        $this->assertArrayHasKey('Location', $ro->headers);
        $this->assertStringContainsString('loginId=fresh-admin', $ro->headers['Location']);

        // Read-back through the resource layer confirms the row
        // landed in dtb_member (vs only living on the Final).
        $next = $this->resource->get('page://self/admin/member', ['loginId' => 'fresh-admin']);
        $this->assertSame(Code::OK, $next->code);
        $this->assertSame('新人管理者', $next->body['name']);
    }

    public function testOnPostDuplicateLoginIdReturns409(): void
    {
        $ro = $this->resource->post('page://self/admin/member', [
            'loginId' => 'test-admin', // already exists
            'password' => 'duplicate-attempt-2026',
            'name' => '別人',
            'authority' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(409, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/member', [
            'loginId' => 'csrf-victim',
            'password' => 'csrf-victim-password-2026',
            'name' => 'CSRF被害者',
            'authority' => 1,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPutUpdatesAdmin(): void
    {
        $ro = $this->resource->put('page://self/admin/member', [
            'loginId' => 'shop-owner',
            'name' => '新店舗オーナー',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('新店舗オーナー', $ro->body['name']);
        $this->assertArrayNotHasKey('mailAddress', $ro->body);

        // Read-back confirms persistence.
        $next = $this->resource->get('page://self/admin/member', ['loginId' => 'shop-owner']);
        $this->assertSame('新店舗オーナー', $next->body['name']);
    }

    public function testOnPutUnknownLoginIdReturns404(): void
    {
        $ro = $this->resource->put('page://self/admin/member', [
            'loginId' => 'no-such-admin',
            'name' => 'irrelevant',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testSortNoMoveUpdatesMemberSortNo(): void
    {
        $ro = $this->resource->put('page://self/admin/sort-no-move', [
            'masterType' => 'member',
            'rowId' => $this->shopOwnerId,
            'sortNo' => 12,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('member', $ro->body['masterType']);
        $this->assertSame(12, $ro->body['sortNo']);

        $next = $this->resource->get('page://self/admin/member', ['loginId' => 'shop-owner']);
        $this->assertSame(Code::OK, $next->code);
        $this->assertSame(12, $next->body['sortNo']);
    }

    public function testOnDeleteSoftDeletesAdmin(): void
    {
        $ro = $this->resource->delete('page://self/admin/member', [
            'loginId' => 'shop-owner',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('shop-owner', $ro->body['loginId']);
        $this->assertFalse($ro->body['alreadyDeleted']);
        $this->assertStringContainsString('削除', $ro->body['message']);

        // Soft-delete: the row is still fetchable (work flipped to 0,
        // not removed) — proving SqlAdminCommand::delete is a flag
        // flip, not a DELETE statement.
        $next = $this->resource->get('page://self/admin/member', ['loginId' => 'shop-owner']);
        $this->assertSame(Code::OK, $next->code);
        $this->assertSame(0, $next->body['work']);
    }

    public function testOnDeleteSelfReturns403(): void
    {
        // test-admin tries to delete themselves — the self-delete
        // guard compares the session adminId against the target's id.
        $ro = $this->resource->delete('page://self/admin/member', [
            'loginId' => 'test-admin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('権限', $ro->body['message']);
    }

    public function testOnDeleteReDeleteReturns200WithFlag(): void
    {
        $this->resource->delete('page://self/admin/member', [
            'loginId' => 'shop-owner',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $ro = $this->resource->delete('page://self/admin/member', [
            'loginId' => 'shop-owner',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertTrue($ro->body['alreadyDeleted']);
        $this->assertStringContainsString('既に削除', $ro->body['message']);
    }
}
