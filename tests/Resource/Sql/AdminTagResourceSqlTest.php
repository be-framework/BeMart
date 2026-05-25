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
 * SQL-backed hypermedia coverage for the admin Tag endpoints —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminTagResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URIs, same body-shape assertions, same AUTHN / AUTHZ / CSRF
 * branches. The only differences are:
 *
 *  - the storage binding (TagStorageInterface → SqlTagStorage, +
 *    TagIdQueryInterface → direct MediaQuery tag id proxy) is layered via
 *    the base class's sqlOverrideModule.
 *
 *  - tagIds are numeric strings drawn from dtb_tag.id, not the
 *    `tg-` prefixed hex the FakeTagIdProvider emits. The two suites
 *    therefore both assert "the response carries a tagId" but only
 *    the Fake side ever asserts on the literal seed handles
 *    (`tg-new` / `tg-sale`) — those are Fake-only and SqlTagStorage
 *    rejects them as non-numeric on lookup (the same 404 path as
 *    any unknown id, by design).
 *
 *  - the Fake's testListIncludesSeed leans on the TagStorageInterface
 *    constructor seeding two rows; dtb_tag is empty on each test, so
 *    the SQL sibling seeds two tags through the resource layer first
 *    and asserts the same `count >= 2` shape afterwards.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but
 * the Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — that's a contract change masquerading
 * as a storage change.
 */
final class AdminTagResourceSqlTest extends AbstractResourceSqlTestCase
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
     * Seed a single tag through the resource layer and return the
     * server-generated tagId — mirrors the Fake-backed sibling's
     * helper exactly. The POST drives the full Becoming chain
     * (Input → Final → direct MediaQuery tag id proxy → SqlTagStorage) so the row
     * appears in the same transactional state every subsequent
     * assertion will see.
     */
    private function seed(string $name): string
    {
        $ro = $this->resource->post('page://self/admin/tag/tag-list', [
            'tagName' => $name,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $id = $ro->body['tagId'];
        assert(is_string($id));

        return $id;
    }

    public function testListIncludesSeed(): void
    {
        // TagStorageInterface seeds tg-new / tg-sale in its constructor;
        // dtb_tag is empty on each test, so seed the equivalent two
        // rows through the resource layer first. The assertion shape
        // matches the Fake-backed sibling.
        $this->seed('新商品');
        $this->seed('セール');

        $ro = $this->resource->get('page://self/admin/tag/tag-list');
        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(2, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/tag/tag-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testCreateHappyPath(): void
    {
        $ro = $this->resource->post('page://self/admin/tag/tag-list', [
            'tagName' => '限定',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('限定', $ro->body['tagName']);
        $this->assertNotEmpty($ro->body['tagId']);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->post('page://self/admin/tag/tag-list', [
            'tagName' => '限定',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testDeleteHappyPath(): void
    {
        $id = $this->seed('Tmp');
        $ro = $this->resource->delete('page://self/admin/tag/tag', [
            'tagId' => $id,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $ro->code);
    }

    public function testDeleteUnknownReturns404(): void
    {
        $ro = $this->resource->delete('page://self/admin/tag/tag', [
            'tagId' => 'nonexistent',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }
}
