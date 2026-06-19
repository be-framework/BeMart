<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Csrf\CsrfTokenInterface;
use Ray\Csrf\Exception\MissingCsrfTokenException;
use Ray\Csrf\Http\CompositeRequestToken;
use Ray\Csrf\Http\RequestTokenInterface;
use MyVendor\BeMart\Module\TestModule;
use MyVendor\BeMart\Support\Resource\HtmlMutationResponse;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;
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
    private const FOOD_CATEGORY_ID = 'cat-food';
    private const DRINKS_CATEGORY_ID = 'cat-drinks';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId, bool $htmlMutation = false): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session, $htmlMutation) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly bool $htmlMutation,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
                if ($this->htmlMutation) {
                    $this->bind(MutationResponseInterface::class)->to(HtmlMutationResponse::class);
                }
                $this->bind(CsrfTokenInterface::class)->to(FakeCsrfToken::class);
                $this->bind(RequestTokenInterface::class)->to(CompositeRequestToken::class);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    private function seed(string $name, int $sortNo = 0): string
    {
        // Fake context is static-fixture based; category rows are supplied
        // by tcategory_get.jsonl / tcategory_list.jsonl.
        unset($sortNo);

        return $name === 'Drinks' ? self::DRINKS_CATEGORY_ID : self::FOOD_CATEGORY_ID;
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

    public function testCreateHtmlContextRedirectsToCategoryDetail(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID, true);
        $ro = $this->resource->post('page://self/admin/category/category-list', [
                'categoryName' => 'Food',
                'sortNo' => 10,
                'csrfToken' => FakeCsrfToken::TOKEN,
            ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertStringContainsString('/admin/category/category?categoryId=', $ro->headers['Location']);
    }

    public function testCreateRejectsMissingCsrf(): void
    {
        $this->expectException(MissingCsrfTokenException::class);
        $this->resource->post('page://self/admin/category/category-list', [
            'categoryName' => 'Food',
            'sortNo' => 10,
        ]);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->post('page://self/admin/category/category-list', [
            'categoryName' => 'Food',
            'sortNo' => 10,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testCreateRejectsUnknownParent(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\CategoryNotFoundException::class);

        $this->resource->post('page://self/admin/category/category-list', [
            'categoryName' => 'Cookies',
            'sortNo' => 20,
            'parentId' => 'nonexistent-zzz',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
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
        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->get('page://self/admin/category/category-list');
    }

    public function testGetReturnsDetail(): void
    {
        $id = $this->seed('Food', 7);

        $ro = $this->resource->get('page://self/admin/category/category', ['categoryId' => $id]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('Food', $ro->body['categoryName']);
        $this->assertSame(10, $ro->body['sortNo']);
    }

    public function testGetUnknownIdReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\CategoryNotFoundException::class);

        $this->resource->get('page://self/admin/category/category', [
            'categoryId' => 'nonexistent-zzz',
        ]);
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

    public function testPutHtmlContextRedirectsToCategoryDetail(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID, true);
        $id = $this->seed('Food', 10);
        $ro = $this->resource->put('page://self/admin/category/category', [
                'categoryId' => $id,
                'categoryName' => 'Foods',
                'csrfToken' => FakeCsrfToken::TOKEN,
            ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/admin/category/category?categoryId=' . $id, $ro->headers['Location']);
    }

    public function testPutRejectsMissingCsrf(): void
    {
        $id = $this->seed('Food');
        $this->expectException(MissingCsrfTokenException::class);
        $this->resource->put('page://self/admin/category/category', [
            'categoryId' => $id,
            'categoryName' => 'X',
        ]);
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

    public function testDeleteHtmlContextRedirectsToCategoryList(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID, true);
        $id = $this->seed('Food');
        $ro = $this->resource->delete('page://self/admin/category/category', [
                'categoryId' => $id,
                'csrfToken' => FakeCsrfToken::TOKEN,
            ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/admin/category/category-list', $ro->headers['Location']);
    }

    public function testDeleteUnknownIdReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\CategoryNotFoundException::class);

        $this->resource->delete('page://self/admin/category/category', [
            'categoryId' => 'nonexistent-zzz',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testExportCsvDumpsRows(): void
    {
        $this->seed('Food', 10);

        $ro = $this->resource->get('page://self/admin/category/csv');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['rowCount']);
        $this->assertTrue(str_contains($ro->body['csv'], 'Food'));
        $this->assertSame('text/csv; charset=UTF-8', $ro->headers['Content-Type']);
    }

    public function testImportCsvPersistsRows(): void
    {
        $ro = $this->resource->post('page://self/admin/category/csv', [
            'csv' => "カテゴリID,カテゴリ名,親カテゴリID,カテゴリ削除フラグ\ncat-food,食品,,0\n",
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doImportCategoryCsv', $ro->body['transitionId']);
        $this->assertTrue($ro->body['accepted']);
        $this->assertSame(1, $ro->body['imported']);
    }

    public function testImportCsvRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->post('page://self/admin/category/csv', [
            'csv' => "foo\nbar",
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }
}
