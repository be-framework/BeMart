<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\CategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\FakeCategoryStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;
use function is_string;
use function str_contains;

/**
 * Wave 7 — resource-layer coverage for the admin Category endpoints.
 *
 *   - GET    /admin/category/category-list   goCategoryList
 *   - POST   /admin/category/category-list   doCreateCategory
 *   - GET    /admin/category/category        goCategory
 *   - PUT    /admin/category/category        doUpdateCategory
 *   - DELETE /admin/category/category        doDeleteCategory
 *   - GET    /admin/category/csv             goExportCategory
 *   - POST   /admin/category/csv             doImportCategoryCsv
 *                                             (Phase 2 stub)
 */
final class AdminCategoryResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private FakeCategoryStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new FakeCategoryStorage();
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session, $this->storage) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly FakeCategoryStorage $storage,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
                $this->bind(CategoryStorageInterface::class)->toInstance($this->storage);
                $this->bind(FakeCategoryStorage::class)->toInstance($this->storage);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

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

    public function testCreateRejectsMissingCsrf(): void
    {
        $ro = $this->resource->post('page://self/admin/category/category-list', [
            'categoryName' => 'Food',
            'sortNo' => 10,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertTrue(str_contains($ro->body['message'], 'CSRF'));
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
        $ro = $this->resource->post('page://self/admin/category/category-list', [
            'categoryName' => 'Cookies',
            'sortNo' => 20,
            'parentId' => 'nonexistent-zzz',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
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

    public function testPutRejectsMissingCsrf(): void
    {
        $id = $this->seed('Food');
        $ro = $this->resource->put('page://self/admin/category/category', [
            'categoryId' => $id,
            'categoryName' => 'X',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertTrue(str_contains($ro->body['message'], 'CSRF'));
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
