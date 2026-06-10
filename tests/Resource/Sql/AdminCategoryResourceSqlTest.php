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
use function str_contains;

/**
 * SQL-backed hypermedia coverage for the admin Category endpoints —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AdminCategoryResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same URIs (`page://self/admin/category/category-list`,
 * `page://self/admin/category/category`,
 * `page://self/admin/category/csv`), same body-shape assertions, same
 * AUTHN / CSRF / parent-resolution branches. The only differences are:
 *
 *  - the storage binding (CategoryStorageInterface → SqlCategoryStorage)
 *    and id query (CategoryIdQueryInterface →
 *    direct MediaQuery category id proxy) are layered via the base class's
 *    sqlOverrideModule; persistence is against the real dtb_category
 *    table.
 *
 *  - categoryIds are numeric strings drawn from dtb_category.id, not
 *    the 32-char hex the FakeCategoryIdProvider emits. Both suites
 *    assert "the response carries a categoryId" but only the Fake side
 *    ever observes a hex handle — SqlCategoryStorage rejects a
 *    non-numeric id as non-numeric on lookup (the same 404 path as any
 *    unknown id, by design). `nonexistent-zzz` therefore folds to a
 *    404 on both backends, so the unknown-id / unknown-parent cases
 *    mirror exactly.
 *
 *  - dtb_category is empty on each test (the per-test transaction
 *    rolls back), so the list / get / put / delete cases seed their
 *    own rows through the resource layer first — same shape the Fake
 *    sibling uses (CategoryStorageInterface also starts empty).
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but
 * the Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — that's a contract change masquerading
 * as a storage change.
 */
final class AdminCategoryResourceSqlTest extends AbstractResourceSqlTestCase
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
     * Seed a single category through the resource layer and return the
     * server-generated categoryId — mirrors the Fake-backed sibling's
     * helper exactly. The POST drives the full Becoming chain (Input →
     * CategoryCreated → direct MediaQuery category id proxy → SqlCategoryStorage) so
     * the row appears in the same transactional state every subsequent
     * assertion will see.
     */
    private function seed(string $name, int $sortNo = 0): string
    {
        $ro = $this->resource->post('page://self/admin/category/category-list', [
            'categoryName' => $name,
            'sortNo' => $sortNo,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $id = $ro->body['categoryId'];
        assert(is_string($id));

        return $id;
    }

    public function testCreateHappyPathReturns201(): void
    {
        $ro = $this->resource->post('page://self/admin/category/category-list', [
            'categoryName' => 'Food',
            'sortNo' => 10,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('Food', $ro->body['categoryName']);
        $this->assertArrayHasKey('Location', $ro->headers);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/category/category-list', [
            'categoryName' => 'Food',
            'sortNo' => 10,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertTrue(str_contains($ro->body['message'], '管理者'));
    }

    public function testCreateRejectsUnknownParent(): void
    {
        // `nonexistent-zzz` is non-numeric — SqlCategoryStorage::getById
        // surfaces it as a miss, so CategoryCreated's parent probe
        // raises CategoryNotFoundException (404), identical to the
        // Fake-backed sibling.
        $ro = $this->resource->post('page://self/admin/category/category-list', [
            'categoryName' => 'Cookies',
            'sortNo' => 20,
            'parentId' => 'nonexistent-zzz',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testCreateChildUnderExistingParentSucceeds(): void
    {
        // SQL-specific reinforcement of the self-FK path: a child
        // category created under a real parent persists, and the
        // parentId round-trips. The Fake sibling's testCreateRejects-
        // UnknownParent only exercises the miss branch — this asserts
        // the hit branch end-to-end so the self-FK INSERT ordering is
        // covered at the Resource layer.
        $parentId = $this->seed('Food', 10);

        $ro = $this->resource->post('page://self/admin/category/category-list', [
            'categoryName' => 'Cookies',
            'sortNo' => 20,
            'parentId' => $parentId,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('Cookies', $ro->body['categoryName']);
        $this->assertSame($parentId, $ro->body['parentId']);
    }

    public function testListReturnsCount(): void
    {
        $this->seed('Food', 10);
        $this->seed('Drinks', 20);

        $ro = $this->resource->get('page://self/admin/category/category-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/category/category-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testGetReturnsDetail(): void
    {
        $id = $this->seed('Food', 7);

        $ro = $this->resource->get('page://self/admin/category/category', ['categoryId' => $id]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('Food', $ro->body['categoryName']);
        $this->assertSame(7, $ro->body['sortNo']);
    }

    public function testGetUnknownIdReturns404(): void
    {
        $ro = $this->resource->get('page://self/admin/category/category', [
            'categoryId' => 'nonexistent-zzz',
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testPutMergesPartialFields(): void
    {
        $id = $this->seed('Food', 10);

        $ro = $this->resource->put('page://self/admin/category/category', [
            'categoryId' => $id,
            'categoryName' => 'Foods',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('Foods', $ro->body['categoryName']);
        $this->assertSame(10, $ro->body['sortNo']);
    }

    public function testDeleteHappyPath(): void
    {
        $id = $this->seed('Food');

        $ro = $this->resource->delete('page://self/admin/category/category', [
            'categoryId' => $id,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($id, $ro->body['categoryId']);
    }

    public function testDeleteUnknownIdReturns404(): void
    {
        $ro = $this->resource->delete('page://self/admin/category/category', [
            'categoryId' => 'nonexistent-zzz',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testExportCsvDumpsRows(): void
    {
        $this->seed('Food', 10);

        $ro = $this->resource->get('page://self/admin/category/csv');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(1, $ro->body['rowCount']);
        $this->assertTrue(str_contains($ro->body['csv'], 'Food'));
        $this->assertSame('text/csv; charset=UTF-8', $ro->headers['Content-Type']);
    }

    public function testImportCsvIsStubReturning202(): void
    {
        $ro = $this->resource->post('page://self/admin/category/csv', [
            'csv' => "categoryName,sortNo\nFood,10\n",
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::ACCEPTED, $ro->code);
        $this->assertFalse($ro->body['accepted']);
        $this->assertSame(2, $ro->body['lineCount']);
    }

    public function testImportCsvRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->post('page://self/admin/category/csv', [
            'csv' => "foo\nbar",
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
