<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;

/**
 * SQL-backed hypermedia coverage for the admin login POST endpoint —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminLoginResourceTest}
 * (Admin auth Phase B, G-23 storage migration contract).
 *
 * Same URI (`page://self/admin/login`), same body-shape assertions,
 * same UNAUTHORIZED / FORBIDDEN / BAD_REQUEST branches. The
 * differences from the Fake-backed sibling:
 *
 *  - the storage binding (AdminQueryInterface → SqlAdminQuery) is
 *    layered via the base class's sqlOverrideModule; the credential
 *    lookup runs against a real dtb_member row.
 *
 *  - the seeded admin's `adminId` is the numeric dtb_member.id (the
 *    Fake hard-codes `ad000…01`). The login response body echoes
 *    that id back so the assertion checks `is_numeric` rather than a
 *    fixed string — same shape parity as AddressBookResourceSqlTest's
 *    numeric customer ids.
 *
 *  - the password fixture: insertAdmin defaults the `password` column
 *    to the SAME bcrypt hash var/fake/admins.json carries for
 *    `test-admin` (the real `password_hash('local-dev-admin-password',
 *    PASSWORD_DEFAULT)` output). So the happy-path login verifies with
 *    the identical plaintext the Fake-backed test uses — proving the
 *    storage swap is transparent to the credential check.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. A divergence means the
 * storage swap changed client-observable behavior.
 */
final class AdminLoginResourceSqlTest extends AbstractResourceSqlTestCase
{
    /** Plaintext whose bcrypt the insertAdmin fixture default carries. */
    private const ADMIN_PASSWORD = 'local-dev-admin-password';

    protected function setUp(): void
    {
        parent::setUp();

        // mtb_work / mtb_authority are empty in the structure-only
        // dump; seed the EC-CUBE canonical rows so the dtb_member FK
        // constraints are satisfiable on insertAdmin.
        $this->seedAdminMasters();

        // The login subject — a real dtb_member row whose password
        // column is the bcrypt of self::ADMIN_PASSWORD (insertAdmin
        // default). authority_id=0 = system admin.
        $this->insertAdmin([
            'loginId' => 'test-admin',
            'name' => 'テスト管理者',
            'authority_id' => 0,
            'work_id' => 1,
        ]);
    }

    public function testOnPostAuthenticatesAndReturns200(): void
    {
        $ro = $this->resource->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => self::ADMIN_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        // adminId is the numeric dtb_member.id (SQL) vs the Fake's
        // hard-coded hex — assert it is a non-empty numeric string.
        $this->assertNotEmpty($ro->body['adminId']);
        $this->assertTrue(ctype_digit((string) $ro->body['adminId']));
        $this->assertSame('test-admin', $ro->body['loginId']);
        $this->assertSame('テスト管理者', $ro->body['name']);
        $this->assertSame(0, $ro->body['authority']);
        $this->assertArrayHasKey('Location', $ro->headers);
    }

    public function testOnPostWrongPasswordReturns401(): void
    {
        $ro = $this->resource->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => 'not-the-right-password',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
        $this->assertStringContainsString('正しくありません', $ro->body['message']);
        // Anti-enumeration: the loginId is NOT echoed back.
        $this->assertArrayNotHasKey('loginId', $ro->body);
    }

    public function testOnPostUnknownLoginIdReturns401(): void
    {
        // A loginId with no dtb_member row — SqlAdminQuery::findByLoginId
        // returns null, AdminAuthenticated raises the same
        // AdminLoginFailedException as a wrong password (no enumeration).
        $ro = $this->resource->post('page://self/admin/login', [
            'loginId' => 'no-such-admin',
            'password' => self::ADMIN_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => self::ADMIN_PASSWORD,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPostShortPasswordReturns400(): void
    {
        $ro = $this->resource->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => 'short',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }
}
