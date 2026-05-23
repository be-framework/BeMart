<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeNewsStorage;
use MyVendor\BeMart\Be\Reason\Query\NewsStorageInterface;
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

/**
 * Wave 9 — resource-layer coverage for the admin News endpoints.
 */
final class AdminNewsResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private FakeNewsStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new FakeNewsStorage();
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session, $this->storage) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly FakeNewsStorage $storage,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
                $this->bind(NewsStorageInterface::class)->toInstance($this->storage);
                $this->bind(FakeNewsStorage::class)->toInstance($this->storage);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

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
