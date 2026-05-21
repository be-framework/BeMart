<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Form\AdminCategoryForm;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;
use function is_string;

/**
 * Resource-layer coverage for the admin カテゴリ登録 / カテゴリ編集
 * Product Tier-2 page.
 *
 * The resource is a thin GET renderer for EC-CUBE's
 * `Product/category.twig` tree-list + inline add/edit screen. An empty
 * categoryId renders the tree list + a blank "新規カテゴリ" form (works
 * with empty Fake storage); a known categoryId pre-fills; an unknown
 * categoryId is 404. The AUTHZ guard rejects anonymous admins.
 */
final class AdminCategoryEditResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        });

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

    public function testOnGetNewRendersBlankEditor(): void
    {
        $ro = $this->resource->get('page://self/admin/category/edit');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminCategoryForm::class, $ro->body['form']);
        $this->assertSame('', $ro->body['categoryId']);
        $this->assertNull($ro->body['category']);
        $this->assertIsArray($ro->body['categories']);
    }

    public function testOnGetKnownCategoryPreFillsEditor(): void
    {
        $id = $this->seed('Food', 7);

        $ro = $this->resource->get('page://self/admin/category/edit', ['categoryId' => $id]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminCategoryForm::class, $ro->body['form']);
        $this->assertSame($id, $ro->body['categoryId']);
        $this->assertSame('Food', $ro->body['category']['categoryName']);
    }

    public function testOnGetUnknownCategoryReturns404(): void
    {
        $ro = $this->resource->get('page://self/admin/category/edit', ['categoryId' => 'nonexistent-zzz']);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnGetRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/category/edit');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
