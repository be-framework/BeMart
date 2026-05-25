<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

use function assert;
use function is_string;

/**
 * SQL-backed hypermedia coverage for the admin Block endpoints —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminBlockResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URIs (`page://self/admin/block/block-list` and
 * `page://self/admin/block/block`), same body-shape assertions, same
 * AUTHN / AUTHZ / CSRF branches. The only differences are:
 *
 *  - the storage binding (BlockStorageInterface → SqlBlockStorage) and
 *    id query (BlockIdQueryInterface → direct MediaQuery block id proxy) are
 *    layered via the base class's sqlOverrideModule; persistence is
 *    against the real dtb_block table.
 *
 *  - blockIds are numeric strings drawn from dtb_block.id, not the
 *    `bk-` prefixed hex the FakeBlockIdProvider emits. Both suites
 *    assert "the response carries a blockId" but only the Fake side
 *    ever observes the literal seed handle (`bk-header`) —
 *    SqlBlockStorage rejects it as non-numeric on lookup (the same 404
 *    path as any unknown id, by design).
 *
 *  - the Fake's testListIncludesSeed leans on the BlockStorageInterface
 *    constructor seeding one undeletable system block (`bk-header`);
 *    dtb_block is empty on each test, so the SQL sibling seeds one
 *    user-block row through the resource layer first and asserts the
 *    same `count >= 1` shape afterwards. The system-block deletion
 *    test is mirrored by seeding a row with deletable = 0 via
 *    insertBlock().
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but
 * the Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — that's a contract change masquerading
 * as a storage change.
 */
final class AdminBlockResourceSqlTest extends AbstractResourceSqlTestCase
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

    /**
     * Seed a single user-editable block through the resource layer and
     * return the server-generated blockId — mirrors the Fake-backed
     * sibling's helper exactly. The POST drives the full Becoming
     * chain (Input → Final → direct MediaQuery block id proxy → SqlBlockStorage) so
     * the row appears in the same transactional state every
     * subsequent assertion will see.
     */
    private function seed(string $name, string $file): string
    {
        $ro = $this->resource->post('page://self/admin/block/block-list', [
            'blockName' => $name,
            'blockFileName' => $file,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $id = $ro->body['blockId'];
        assert(is_string($id));

        return $id;
    }

    public function testListIncludesSeed(): void
    {
        // BlockStorageInterface seeds `bk-header` in its constructor;
        // dtb_block is empty on each test, so seed an equivalent row
        // through the resource layer first. The assertion shape
        // matches the Fake-backed sibling.
        $this->seed('バナー', 'banner');

        $ro = $this->resource->get('page://self/admin/block/block-list');
        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(1, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/block/block-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testCreateHappyPath(): void
    {
        $ro = $this->resource->post('page://self/admin/block/block-list', [
            'blockName' => 'バナー',
            'blockFileName' => 'banner',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('バナー', $ro->body['blockName']);
        $this->assertTrue($ro->body['blockDeletable']);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->post('page://self/admin/block/block-list', [
            'blockName' => 'バナー',
            'blockFileName' => 'banner',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testUpdateMerges(): void
    {
        $id = $this->seed('Old', 'old');
        $ro = $this->resource->put('page://self/admin/block/block', [
            'blockId' => $id,
            'blockName' => 'New',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('New', $ro->body['blockName']);
    }

    public function testDeleteUserBlockHappyPath(): void
    {
        $id = $this->seed('Tmp', 'tmp');
        $ro = $this->resource->delete('page://self/admin/block/block', [
            'blockId' => $id,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $ro->code);
    }

    public function testDeleteSystemBlockIsRefused(): void
    {
        // Mirror of the Fake-backed sibling's check: a block with
        // blockDeletable=false is system-managed and BlockDeleted maps
        // that to a 404 (masking system-block existence).
        // BlockStorageInterface seeds `bk-header` with blockDeletable=false
        // in its constructor; dtb_block is empty so we seed an
        // equivalent row directly via the fixture helper at
        // deletable = 0.
        $systemId = (string) $this->insertBlock([
            'block_name' => 'ヘッダー',
            'file_name' => 'header',
            'deletable' => 0,
        ]);

        $ro = $this->resource->delete('page://self/admin/block/block', [
            'blockId' => $systemId,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }
}
