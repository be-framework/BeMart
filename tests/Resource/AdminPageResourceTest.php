<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakePageStorage;
use MyVendor\BeMart\Be\Reason\Query\PageStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;
use function is_string;
use function str_contains;

/**
 * Wave 9 — resource-layer coverage for the admin Page endpoints
 * (goPageList / goPage / doCreatePage / doUpdatePage / doDeletePage).
 */
final class AdminPageResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private FakePageStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new FakePageStorage();
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session, $this->storage) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly FakePageStorage $storage,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
                $this->bind(PageStorageInterface::class)->toInstance($this->storage);
                $this->bind(FakePageStorage::class)->toInstance($this->storage);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

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
        $ro = $this->resource->delete('page://self/admin/page/page', [
            'pageId' => FakePageStorage::SEED_PAGE_ID,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }
}
