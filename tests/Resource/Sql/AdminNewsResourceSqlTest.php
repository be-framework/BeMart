<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

use function assert;
use function is_string;

/**
 * SQL-backed hypermedia coverage for the admin News endpoints —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminNewsResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URIs (`page://self/admin/news/news-list` and
 * `page://self/admin/news/news`), same body-shape assertions, same
 * AUTHN / AUTHZ / CSRF branches. The only differences are:
 *
 *  - the storage binding (NewsStorageInterface → SqlNewsStorage) and
 *    id generator (NewsIdGeneratorInterface → direct MediaQuery news id proxy) are
 *    layered via the base class's sqlOverrideModule; persistence is
 *    against the real dtb_news table.
 *
 *  - newsIds are numeric strings drawn from dtb_news.id, not the
 *    `nw-` prefixed hex the FakeNewsIdGenerator emits. Both suites
 *    assert "the response carries a newsId" but only the Fake side
 *    ever observes literal seed handles (`nw-welcome`) — SqlNewsStorage
 *    rejects them as non-numeric on lookup (the same 404 path as any
 *    unknown id, by design).
 *
 *  - the Fake's testListIncludesSeed leans on the NewsStorageInterface
 *    constructor seeding one row; dtb_news is empty on each test, so
 *    the SQL sibling seeds one news post through the resource layer
 *    first and asserts the same `count >= 1` shape afterwards.
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but
 * the Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — that's a contract change masquerading
 * as a storage change.
 */
final class AdminNewsResourceSqlTest extends AbstractResourceSqlTestCase
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
     * Seed a single news post through the resource layer and return
     * the server-generated newsId — mirrors the Fake-backed sibling's
     * helper exactly. The POST drives the full Becoming chain
     * (Input → Final → direct MediaQuery news id proxy → SqlNewsStorage) so the
     * row appears in the same transactional state every subsequent
     * assertion will see.
     */
    private function seed(string $title): string
    {
        $ro = $this->resource->post('page://self/admin/news/news-list', [
            'newsTitle' => $title,
            'publishDate' => '2026-05-01T00:00:00+09:00',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $id = $ro->body['newsId'];
        assert(is_string($id));

        return $id;
    }

    public function testListIncludesSeed(): void
    {
        // NewsStorageInterface seeds `nw-welcome` in its constructor;
        // dtb_news is empty on each test, so seed an equivalent row
        // through the resource layer first. The assertion shape
        // matches the Fake-backed sibling.
        $this->seed('Welcome');

        $ro = $this->resource->get('page://self/admin/news/news-list');
        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(1, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/news/news-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testCreateHappyPathReturns201(): void
    {
        $ro = $this->resource->post('page://self/admin/news/news-list', [
            'newsTitle' => '新店舗オープン',
            'publishDate' => '2026-05-01T00:00:00+09:00',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('新店舗オープン', $ro->body['newsTitle']);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->post('page://self/admin/news/news-list', [
            'newsTitle' => 'X',
            'publishDate' => '2026-05-01T00:00:00+09:00',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testGetHappyPath(): void
    {
        $id = $this->seed('Hello');
        $ro = $this->resource->get('page://self/admin/news/news', ['newsId' => $id]);
        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('Hello', $ro->body['newsTitle']);
    }

    public function testGetUnknownIdReturns404(): void
    {
        $ro = $this->resource->get('page://self/admin/news/news', ['newsId' => 'nonexistent']);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testUpdateMerges(): void
    {
        $id = $this->seed('Old');
        $ro = $this->resource->put('page://self/admin/news/news', [
            'newsId' => $id,
            'newsTitle' => 'New',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('New', $ro->body['newsTitle']);
    }

    public function testDeleteHappyPath(): void
    {
        $id = $this->seed('Tmp');
        $ro = $this->resource->delete('page://self/admin/news/news', [
            'newsId' => $id,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $ro->code);
    }

    public function testDeleteUnknownReturns404(): void
    {
        $ro = $this->resource->delete('page://self/admin/news/news', [
            'newsId' => 'nonexistent',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }
}
