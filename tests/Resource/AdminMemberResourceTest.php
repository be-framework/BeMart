<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Wave 8 admin Member resource — GET / POST / PUT / DELETE share one
 * URL `page://self/admin/member`. Tests cover the happy paths + the
 * AUTHZ ladder for each verb.
 */
final class AdminMemberResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const SHOP_OWNER_ID = 'ad000000000000000000000000000002';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetHappyPathReturnsAdminDetail(): void
    {
        $ro = $this->resource->get('page://self/admin/member', ['loginId' => 'shop-owner']);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('shop-owner', $ro->body['loginId']);
        $this->assertSame('店舗オーナー', $ro->body['name']);
        $this->assertSame(1, $ro->body['authority']);
        $this->assertArrayNotHasKey('passwordHash', $ro->body);
    }

    public function testOnGetUnknownLoginIdReturns404(): void
    {
        $ro = $this->resource->get('page://self/admin/member', ['loginId' => 'no-such-admin']);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnGetAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/member', ['loginId' => 'shop-owner']);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnPostCreatesNewAdmin(): void
    {
        $ro = $this->resource->post('page://self/admin/member', [
            'loginId' => 'fresh-admin',
            'password' => 'fresh-admin-password-2026',
            'name' => '新人管理者',
            'authority' => 1,
            'mailAddress' => 'fresh-admin@example.com',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('fresh-admin', $ro->body['loginId']);
        $this->assertSame(1, $ro->body['authority']);
        $this->assertSame(1, $ro->body['work']);
        $this->assertArrayHasKey('Location', $ro->headers);
        $this->assertStringContainsString('loginId=fresh-admin', $ro->headers['Location']);
    }

    public function testOnPostDuplicateLoginIdReturns409(): void
    {
        $ro = $this->resource->post('page://self/admin/member', [
            'loginId' => 'test-admin',  // already exists
            'password' => 'duplicate-attempt-2026',
            'name' => '別人',
            'authority' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(409, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/member', [
            'loginId' => 'csrf-victim',
            'password' => 'csrf-victim-password-2026',
            'name' => 'CSRF被害者',
            'authority' => 1,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPutUpdatesAdmin(): void
    {
        $ro = $this->resource->put('page://self/admin/member', [
            'loginId' => 'shop-owner',
            'name' => '新店舗オーナー',
            'mailAddress' => 'new-shop-owner@example.com',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('新店舗オーナー', $ro->body['name']);
        $this->assertSame('new-shop-owner@example.com', $ro->body['mailAddress']);
    }

    public function testOnPutUnknownLoginIdReturns404(): void
    {
        $ro = $this->resource->put('page://self/admin/member', [
            'loginId' => 'no-such-admin',
            'name' => 'irrelevant',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnDeleteSoftDeletesAdmin(): void
    {
        $ro = $this->resource->delete('page://self/admin/member', [
            'loginId' => 'shop-owner',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('shop-owner', $ro->body['loginId']);
        $this->assertFalse($ro->body['alreadyDeleted']);
        $this->assertStringContainsString('削除', $ro->body['message']);
    }

    public function testOnDeleteSelfReturns403(): void
    {
        // test-admin tries to delete themselves.
        $ro = $this->resource->delete('page://self/admin/member', [
            'loginId' => 'test-admin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('権限', $ro->body['message']);
    }

    public function testOnDeleteReDeleteReturns200WithFlag(): void
    {
        $this->resource->delete('page://self/admin/member', [
            'loginId' => 'shop-owner',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $ro = $this->resource->delete('page://self/admin/member', [
            'loginId' => 'shop-owner',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertTrue($ro->body['alreadyDeleted']);
        $this->assertStringContainsString('既に削除', $ro->body['message']);
    }
}
