<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlAdminCommand;
use MyVendor\BeMart\Be\Reason\Query\SqlAdminQuery;
use MyVendor\BeMart\Be\Reason\Service\SqlAdminIdGenerator;

/**
 * Storage-layer coverage for {@see SqlAdminCommand} (Admin auth Phase B).
 *
 * Mirrors the shape of {@see SqlAddressStorageTest}'s write half. Per
 * G-23 the client-observable contract lives in the Resource-layer
 * siblings; this file pins the per-method SQL paths (INSERT with
 * pre-allocated id, UPDATE on update, soft-delete flips work_id to 0,
 * single-column authority flip).
 */
final class SqlAdminCommandTest extends AbstractSqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // mtb_work / mtb_authority are empty in the structure-only
        // dump; seed the EC-CUBE canonical rows so dtb_member's FKs
        // (work_id / authority_id) are satisfiable on every write.
        $this->seedAdminMasters();
    }

    public function testCreateInsertsRowWithProvidedId(): void
    {
        $generator = new SqlAdminIdGenerator($this->pdo);
        $newId = $generator->generate(); // numeric string

        $command = new SqlAdminCommand($this->pdo);
        $command->create(new AdminEntity(
            adminId: $newId,
            loginId: 'fresh-1',
            passwordHash: '$2y$12$hash',
            name: 'Fresh Admin',
            authority: 1,
            work: AdminEntity::WORK_ACTIVE,
        ));

        $query = new SqlAdminQuery($this->pdo);
        $read = $query->findById($newId);

        $this->assertInstanceOf(AdminEntity::class, $read);
        $this->assertSame($newId, $read->adminId);
        $this->assertSame('fresh-1', $read->loginId);
        $this->assertSame('Fresh Admin', $read->name);
        $this->assertSame(1, $read->authority);
        $this->assertSame(AdminEntity::WORK_ACTIVE, $read->work);
    }

    public function testCreateIsNoOpForNonNumericId(): void
    {
        // FakeAdminIdGenerator emits `ad…` hex; SqlAdminCommand must
        // reject it silently rather than coerce it into an int PK.
        $command = new SqlAdminCommand($this->pdo);
        $command->create(new AdminEntity(
            adminId: 'ad000000000000000000000000000001',
            loginId: 'reject-me',
            passwordHash: '$2y$12$x',
            name: 'X',
            authority: 0,
            work: AdminEntity::WORK_ACTIVE,
        ));

        $query = new SqlAdminQuery($this->pdo);
        $this->assertNull($query->findByLoginId('reject-me'));
    }

    public function testCreateAcceptsSystemAdminAuthorityZero(): void
    {
        // authority=0 (system admin) is the most common case and the
        // EC-CUBE seed value. We write NULL to satisfy the empty
        // mtb_authority FK constraint; hydrate coerces back to 0.
        $generator = new SqlAdminIdGenerator($this->pdo);
        $newId = $generator->generate();

        $command = new SqlAdminCommand($this->pdo);
        $command->create(new AdminEntity(
            adminId: $newId,
            loginId: 'system-1',
            passwordHash: '$2y$12$x',
            name: 'System',
            authority: 0,
            work: AdminEntity::WORK_ACTIVE,
        ));

        $query = new SqlAdminQuery($this->pdo);
        $read = $query->findById($newId);
        $this->assertInstanceOf(AdminEntity::class, $read);
        $this->assertSame(0, $read->authority);
    }

    public function testUpdateOverwritesEditableFields(): void
    {
        $id = $this->insertAdmin([
            'login_id' => 'before',
            'name' => '旧名',
            'password' => '$2y$12$oldhash',
            'authority_id' => null, // → 0 on read
        ]);

        $merged = new AdminEntity(
            adminId: (string) $id,
            loginId: 'after', // changed
            passwordHash: '$2y$12$newhash',
            name: '新名',
            authority: 1,
            work: AdminEntity::WORK_ACTIVE,
        );

        $command = new SqlAdminCommand($this->pdo);
        $command->update($merged);

        $query = new SqlAdminQuery($this->pdo);
        $read = $query->findById((string) $id);
        $this->assertInstanceOf(AdminEntity::class, $read);
        $this->assertSame('after', $read->loginId);
        $this->assertSame('新名', $read->name);
        $this->assertSame('$2y$12$newhash', $read->passwordHash);
        $this->assertSame(1, $read->authority);
    }

    public function testUpdateIsNoOpForNonNumericId(): void
    {
        $id = $this->insertAdmin(['login_id' => 'untouched', 'name' => 'Original']);
        $command = new SqlAdminCommand($this->pdo);

        // Use a non-numeric id — the UPDATE must NOT run.
        $command->update(new AdminEntity(
            adminId: 'ad000000000000000000000000000001',
            loginId: 'hijacked',
            passwordHash: '$2y$12$x',
            name: 'Hijacked',
            authority: 0,
            work: AdminEntity::WORK_ACTIVE,
        ));

        $query = new SqlAdminQuery($this->pdo);
        $read = $query->findById((string) $id);
        $this->assertInstanceOf(AdminEntity::class, $read);
        $this->assertSame('untouched', $read->loginId);
        $this->assertSame('Original', $read->name);
    }

    public function testDeleteFlipsWorkToInactive(): void
    {
        $id = $this->insertAdmin(['login_id' => 'soft-delete-target']);

        $command = new SqlAdminCommand($this->pdo);
        $command->delete((string) $id);

        // Row stays in the table — getById still resolves it.
        $query = new SqlAdminQuery($this->pdo);
        $read = $query->findById((string) $id);
        $this->assertInstanceOf(AdminEntity::class, $read);
        $this->assertSame(AdminEntity::WORK_INACTIVE, $read->work);

        // listAll still surfaces it (audit-visible, re-activatable).
        $all = $query->listAll();
        $loginIds = [];
        foreach ($all as $row) {
            $loginIds[] = $row->loginId;
        }

        $this->assertContains('soft-delete-target', $loginIds);
    }

    public function testDeleteIsNoOpForNonNumericId(): void
    {
        $id = $this->insertAdmin(['login_id' => 'untouched']);
        $command = new SqlAdminCommand($this->pdo);

        // Hex id — no UPDATE should run.
        $command->delete('ad000000000000000000000000000001');

        $query = new SqlAdminQuery($this->pdo);
        $read = $query->findById((string) $id);
        $this->assertInstanceOf(AdminEntity::class, $read);
        $this->assertSame(AdminEntity::WORK_ACTIVE, $read->work);
    }

    public function testUpdateAuthorityFlipsSingleColumn(): void
    {
        $id = $this->insertAdmin([
            'login_id' => 'promotable',
            'name' => '元店舗オーナー',
            'authority_id' => 1, // shop owner
        ]);

        $command = new SqlAdminCommand($this->pdo);
        $command->updateAuthority((string) $id, 0); // promote to system admin

        $query = new SqlAdminQuery($this->pdo);
        $read = $query->findById((string) $id);
        $this->assertInstanceOf(AdminEntity::class, $read);
        $this->assertSame(0, $read->authority);
        // Unrelated fields preserved through the narrow update.
        $this->assertSame('promotable', $read->loginId);
        $this->assertSame('元店舗オーナー', $read->name);
    }

    public function testUpdateAuthorityHandlesNonZeroDirection(): void
    {
        $id = $this->insertAdmin([
            'login_id' => 'demote-me',
            'authority_id' => null, // system admin (0 after hydrate)
        ]);

        $command = new SqlAdminCommand($this->pdo);
        $command->updateAuthority((string) $id, 1);

        $query = new SqlAdminQuery($this->pdo);
        $read = $query->findById((string) $id);
        $this->assertInstanceOf(AdminEntity::class, $read);
        $this->assertSame(1, $read->authority);
    }

    public function testUpdateAuthorityIsNoOpForNonNumericId(): void
    {
        $id = $this->insertAdmin(['login_id' => 'untouched', 'authority_id' => 1]);

        $command = new SqlAdminCommand($this->pdo);
        $command->updateAuthority('ad000000000000000000000000000001', 0);

        $query = new SqlAdminQuery($this->pdo);
        $read = $query->findById((string) $id);
        $this->assertInstanceOf(AdminEntity::class, $read);
        $this->assertSame(1, $read->authority);
    }

    public function testSqlAdminIdGeneratorAllocatesIncrementingIds(): void
    {
        $generator = new SqlAdminIdGenerator($this->pdo);

        // Empty table → starts at 1.
        $this->assertSame('1', $generator->generate());

        $firstId = $this->insertAdmin(['login_id' => 'gen-1']);
        $secondId = $this->insertAdmin(['login_id' => 'gen-2']);
        $this->assertSame((string) ($secondId + 1), $generator->generate());
        $this->assertGreaterThan($firstId, $secondId);
    }
}
