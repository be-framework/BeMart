<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlAdminQuery;

/**
 * Storage-layer coverage for {@see SqlAdminQuery} (Admin auth Phase B).
 *
 * Mirrors the shape of {@see SqlAddressStorageTest}. Per G-23 the
 * client-observable contract lives in the Resource-layer siblings
 * under `tests/Resource/Sql/Admin*ResourceSqlTest.php`; the cases
 * below verify the per-method SQL paths in isolation (LIKE escape,
 * NULL coercion, ORDER BY, LIMIT/OFFSET).
 */
final class SqlAdminQueryTest extends AbstractSqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // mtb_work / mtb_authority are empty in the structure-only
        // dump; seed the EC-CUBE canonical rows so the insertAdmin
        // fixture helper's default work_id=1 / authority_id=0 satisfy
        // dtb_member's FK constraints.
        $this->seedAdminMasters();
    }

    public function testFindByLoginIdReturnsHydratedEntity(): void
    {
        $id = $this->insertAdmin([
            'login_id' => 'sql-admin-1',
            'name' => 'SQL Admin',
            'authority_id' => null, // → coerced to 0 on read
            'work_id' => null,      // → coerced to WORK_ACTIVE on read
        ]);

        $query = $this->sql(SqlAdminQuery::class);
        $admin = $query->findByLoginId('sql-admin-1');

        $this->assertInstanceOf(AdminEntity::class, $admin);
        $this->assertSame((string) $id, $admin->adminId);
        $this->assertSame('sql-admin-1', $admin->loginId);
        $this->assertSame('SQL Admin', $admin->name);
        $this->assertSame(0, $admin->authority);
        $this->assertSame(AdminEntity::WORK_ACTIVE, $admin->work);
    }

    public function testFindByLoginIdReturnsNullForMissing(): void
    {
        $query = $this->sql(SqlAdminQuery::class);
        $this->assertNull($query->findByLoginId('no-such-admin'));
    }

    public function testFindByLoginIdProjectsNullNameAsEmptyString(): void
    {
        // dtb_member.name is nullable but AdminEntity::name is non-null;
        // the hydrator coerces NULL → ''.
        $this->insertAdmin(['login_id' => 'no-name-admin', 'name' => null]);

        $query = $this->sql(SqlAdminQuery::class);
        $admin = $query->findByLoginId('no-name-admin');

        $this->assertInstanceOf(AdminEntity::class, $admin);
        $this->assertSame('', $admin->name);
    }

    public function testFindByIdReturnsHydratedEntity(): void
    {
        $id = $this->insertAdmin(['login_id' => 'find-by-id']);

        $query = $this->sql(SqlAdminQuery::class);
        $admin = $query->findById((string) $id);

        $this->assertInstanceOf(AdminEntity::class, $admin);
        $this->assertSame((string) $id, $admin->adminId);
        $this->assertSame('find-by-id', $admin->loginId);
    }

    public function testFindByIdReturnsNullForMissingNumericId(): void
    {
        $query = $this->sql(SqlAdminQuery::class);
        $this->assertNull($query->findById('99999999'));
    }

    public function testFindByIdReturnsNullForNonNumericId(): void
    {
        // A 32-char hex from FakeAdminIdGenerator can never match an
        // int PK — surface as miss so the Final's 404 path fires
        // instead of a PDO error.
        $query = $this->sql(SqlAdminQuery::class);
        $this->assertNull($query->findById('ad000000000000000000000000000001'));
    }

    public function testListAllSortedByLoginIdAscending(): void
    {
        $this->insertAdmin(['login_id' => 'charlie']);
        $this->insertAdmin(['login_id' => 'alice']);
        $this->insertAdmin(['login_id' => 'bob']);

        $query = $this->sql(SqlAdminQuery::class);
        $rows = $query->listAll();

        $this->assertCount(3, $rows);
        $this->assertSame('alice', $rows[0]->loginId);
        $this->assertSame('bob', $rows[1]->loginId);
        $this->assertSame('charlie', $rows[2]->loginId);
    }

    public function testListAllRespectsLimitAndOffset(): void
    {
        $this->insertAdmin(['login_id' => 'a']);
        $this->insertAdmin(['login_id' => 'b']);
        $this->insertAdmin(['login_id' => 'c']);
        $this->insertAdmin(['login_id' => 'd']);

        $query = $this->sql(SqlAdminQuery::class);
        $rows = $query->listAll(limit: 2, offset: 1);

        $this->assertCount(2, $rows);
        $this->assertSame('b', $rows[0]->loginId);
        $this->assertSame('c', $rows[1]->loginId);
    }

    public function testListAllIncludesSoftDeletedRows(): void
    {
        // Soft-deleted (work_id=0) admins MUST stay visible to the grid
        // so a system admin can re-activate. Login flow filters them
        // out separately.
        $this->insertAdmin(['login_id' => 'active', 'work_id' => null]);
        $this->insertAdmin(['login_id' => 'inactive', 'work_id' => 0]);

        $query = $this->sql(SqlAdminQuery::class);
        $rows = $query->listAll();

        $this->assertCount(2, $rows);
        $loginIds = [];
        foreach ($rows as $row) {
            $loginIds[] = $row->loginId;
        }

        $this->assertContains('active', $loginIds);
        $this->assertContains('inactive', $loginIds);
        // The inactive row hydrates with work=0 verbatim.
        foreach ($rows as $row) {
            if ($row->loginId === 'inactive') {
                $this->assertSame(AdminEntity::WORK_INACTIVE, $row->work);
            }
        }
    }

    public function testSearchWithNullKeywordReturnsAllRows(): void
    {
        $this->insertAdmin(['login_id' => 'a', 'name' => '一郎']);
        $this->insertAdmin(['login_id' => 'b', 'name' => '次郎']);

        $query = $this->sql(SqlAdminQuery::class);
        $this->assertCount(2, $query->search(null));
        $this->assertCount(2, $query->search(''));
    }

    public function testSearchSubstringFilter(): void
    {
        $this->insertAdmin(['login_id' => 'system-1', 'name' => 'システム管理者']);
        $this->insertAdmin(['login_id' => 'shop-1', 'name' => '店舗オーナー']);
        $this->insertAdmin(['login_id' => 'deputy-1', 'name' => '副管理者']);

        $query = $this->sql(SqlAdminQuery::class);

        $matches = $query->search('副');
        $this->assertCount(1, $matches);
        $this->assertSame('deputy-1', $matches[0]->loginId);

        $matches = $query->search('管理者');
        $this->assertCount(2, $matches);
    }

    public function testSearchEscapesLikeWildcardsInKeyword(): void
    {
        $this->insertAdmin(['login_id' => 'literal', 'name' => 'literal_underscore']);
        $this->insertAdmin(['login_id' => 'wild', 'name' => 'wildXunderscore']);

        $query = $this->sql(SqlAdminQuery::class);

        // `_` is a single-char wildcard in LIKE; the implementation
        // must escape it so only the literal underscore matches.
        $matches = $query->search('al_un');
        $this->assertCount(1, $matches);
        $this->assertSame('literal', $matches[0]->loginId);
    }

    public function testHydratorCoercesAuthorityIdNullToZero(): void
    {
        $this->insertAdmin(['login_id' => 'sys', 'authority_id' => null]);

        $query = $this->sql(SqlAdminQuery::class);
        $admin = $query->findByLoginId('sys');
        $this->assertInstanceOf(AdminEntity::class, $admin);
        $this->assertSame(0, $admin->authority);
    }
}
