<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use Ray\Di\AbstractModule;

use function assert;
use function is_string;
use function str_contains;

/**
 * SQL-backed hypermedia coverage for the admin Page endpoints —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminPageResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URIs (`page://self/admin/page/page-list` and
 * `page://self/admin/page/page`), same body-shape assertions, same
 * AUTHN / AUTHZ / CSRF branches. The only differences are:
 *
 *  - the storage binding (PageStorageInterface → SqlPageStorage) and
 *    id generator (PageIdGeneratorInterface → SqlPageIdGenerator) are
 *    layered via the base class's sqlOverrideModule; persistence is
 *    against the real dtb_page table.
 *
 *  - pageIds are numeric strings drawn from dtb_page.id, not the
 *    `pg-` prefixed hex the FakePageIdGenerator emits. Both suites
 *    assert "the response carries a pageId" but only the Fake side
 *    ever observes the literal seed handle (`pg-homepage`) —
 *    SqlPageStorage rejects it as non-numeric on lookup (the same 404
 *    path as any unknown id, by design).
 *
 *  - the Fake's testListIncludesSeed leans on the FakePageStorage
 *    constructor seeding one system page (`pg-homepage`); dtb_page is
 *    empty on each test, so the SQL sibling seeds one user-page row
 *    through the resource layer first and asserts the same
 *    `count >= 1` shape afterwards. The system-page deletion test is
 *    mirrored by seeding a row with edit_type = 2 via insertPage().
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but
 * the Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — that's a contract change masquerading
 * as a storage change.
 */
final class AdminPageResourceSqlTest extends AbstractResourceSqlTestCase
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
     * Seed a single user-editable page through the resource layer and
     * return the server-generated pageId — mirrors the Fake-backed
     * sibling's helper exactly. The POST drives the full Becoming
     * chain (Input → Final → SqlPageIdGenerator → SqlPageStorage) so
     * the row appears in the same transactional state every
     * subsequent assertion will see.
     */
    private function seed(string $name, string $url, string $file): string
    {
        $ro = $this->resource->post('page://self/admin/page/page-list', [
            'pageName' => $name,
            'pageUrl' => $url,
            'pageFileName' => $file,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $id = $ro->body['pageId'];
        assert(is_string($id));

        return $id;
    }

    public function testListIncludesSeed(): void
    {
        // FakePageStorage seeds `pg-homepage` in its constructor;
        // dtb_page is empty on each test, so seed an equivalent row
        // through the resource layer first. The assertion shape
        // matches the Fake-backed sibling.
        $this->seed('Welcome', 'welcome', 'welcome');

        $ro = $this->resource->get('page://self/admin/page/page-list');
        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(1, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/page/page-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testCreateHappyPathReturns201(): void
    {
        $ro = $this->resource->post('page://self/admin/page/page-list', [
            'pageName' => '会社案内',
            'pageUrl' => 'company',
            'pageFileName' => 'company',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('会社案内', $ro->body['pageName']);
        $this->assertSame(0, $ro->body['pageEditType']);
        $this->assertArrayHasKey('Location', $ro->headers);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->post('page://self/admin/page/page-list', [
            'pageName' => '会社案内',
            'pageUrl' => 'company',
            'pageFileName' => 'company',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testCreateRejectsMissingCsrf(): void
    {
        $ro = $this->resource->post('page://self/admin/page/page-list', [
            'pageName' => '会社案内',
            'pageUrl' => 'company',
            'pageFileName' => 'company',
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertTrue(str_contains($ro->body['message'], 'CSRF'));
    }

    public function testGetHappyPath(): void
    {
        $id = $this->seed('会社案内', 'company', 'company');
        $ro = $this->resource->get('page://self/admin/page/page', ['pageId' => $id]);
        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('会社案内', $ro->body['pageName']);
    }

    public function testGetUnknownIdReturns404(): void
    {
        $ro = $this->resource->get('page://self/admin/page/page', ['pageId' => 'nonexistent-zzz']);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testUpdateMerges(): void
    {
        $id = $this->seed('Foo', 'foo', 'foo');
        $ro = $this->resource->put('page://self/admin/page/page', [
            'pageId' => $id,
            'pageName' => 'Foo!',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('Foo!', $ro->body['pageName']);
        $this->assertSame('foo', $ro->body['pageUrl']);
    }

    public function testDeleteUserPageHappyPath(): void
    {
        $id = $this->seed('Foo', 'foo', 'foo');
        $ro = $this->resource->delete('page://self/admin/page/page', [
            'pageId' => $id,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($id, $ro->body['pageId']);
    }

    public function testDeleteSystemPageIsRefused(): void
    {
        // Mirror of the Fake-backed sibling's check: a page with
        // pageEditType >= 2 is system-managed and PageDeleted maps
        // that to a 404 (masking system-page existence). FakePageStorage
        // seeds `pg-homepage` with editType = 2 in its constructor;
        // dtb_page is empty so we seed an equivalent row directly via
        // the fixture helper at edit_type = 2.
        $systemId = (string) $this->insertPage([
            'page_name' => 'ホームページ',
            'url' => 'homepage',
            'file_name' => 'index',
            'edit_type' => 2, // EDIT_TYPE_DEFAULT (system page)
        ]);

        $ro = $this->resource->delete('page://self/admin/page/page', [
            'pageId' => $systemId,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }
}
