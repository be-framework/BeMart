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

/**
 * Wave 9 — resource-layer coverage for the admin News endpoints.
 */
final class AdminNewsResourceTest extends TestCase
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

    private function seed(string $title): string
    {
        // Static Ray.FakeQuery fixture, not a mutable seed.
        unset($title);

        return 'nw-welcome';
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
        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->get('page://self/admin/news/news-list');
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
        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->post('page://self/admin/news/news-list', [
            'newsTitle' => 'X',
            'publishDate' => '2026-05-01T00:00:00+09:00',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testGetHappyPath(): void
    {
        $id = $this->seed('Hello');
        $ro = $this->resource->get('page://self/admin/news/news', ['newsId' => $id]);
        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('ようこそ', $ro->body['newsTitle']);
    }

    public function testGetUnknownIdReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\NewsNotFoundException::class);

        $this->resource->get('page://self/admin/news/news', ['newsId' => 'nonexistent']);
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
        $this->expectException(\MyVendor\BeMart\Be\Exception\NewsNotFoundException::class);

        $this->resource->delete('page://self/admin/news/news', [
            'newsId' => 'nonexistent',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }
}
