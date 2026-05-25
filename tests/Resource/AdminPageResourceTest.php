<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;
use function str_contains;

/**
 * Wave 9 — resource-layer coverage for the admin Page endpoints
 * (goPageList / goPage / doCreatePage / doUpdatePage / doDeletePage).
 */
final class AdminPageResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const COMPANY_PAGE_ID = 'pg-company';
    private const FOO_PAGE_ID = 'pg-foo';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    private function seed(string $name, string $url, string $file): string
    {
        // Static Ray.FakeQuery fixture, not a mutable seed.
        unset($url, $file);

        return $name === '会社案内' ? self::COMPANY_PAGE_ID : self::FOO_PAGE_ID;
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
            'pageId' => 'pg-homepage',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }
}
