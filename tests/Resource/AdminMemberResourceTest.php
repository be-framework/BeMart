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
 * Wave 8 admin Member resource — GET / POST / PUT / DELETE share one
 * URL `page://self/admin/member`. Tests cover the happy paths + the
 * AUTHZ ladder for each verb.
 */
final class AdminMemberResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const SHOP_OWNER_ID = 'ad000000000000000000000000000002';
    private const DELETED_LOGIN_ID = 'deleted-admin';

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
        $this->expectException(\MyVendor\BeMart\Be\Exception\AdminNotFoundException::class);

        $this->resource->get('page://self/admin/member', ['loginId' => 'no-such-admin']);
    }

    public function testOnGetAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->get('page://self/admin/member', ['loginId' => 'shop-owner']);
    }

    public function testOnPostCreatesNewAdmin(): void
    {
        $ro = $this->resource->post('page://self/admin/member', [
            'loginId' => 'fresh-admin',
            'password' => 'fresh-admin-password-2026',
            'name' => '新人管理者',
            'authority' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('fresh-admin', $ro->body['loginId']);
        $this->assertSame(1, $ro->body['authority']);
        $this->assertSame(1, $ro->body['work']);
        $this->assertArrayNotHasKey('mailAddress', $ro->body);
        $this->assertArrayHasKey('Location', $ro->headers);
        $this->assertStringContainsString('loginId=fresh-admin', $ro->headers['Location']);
    }

    public function testOnPostDuplicateLoginIdReturns409(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\LoginIdAlreadyTakenException::class);

        $this->resource->post('page://self/admin/member', [
            'loginId' => 'test-admin',  // already exists
            'password' => 'duplicate-attempt-2026',
            'name' => '別人',
            'authority' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPutUpdatesAdmin(): void
    {
        $ro = $this->resource->put('page://self/admin/member', [
            'loginId' => 'shop-owner',
            'name' => '新店舗オーナー',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('新店舗オーナー', $ro->body['name']);
        $this->assertArrayNotHasKey('mailAddress', $ro->body);
    }

    public function testOnPutUnknownLoginIdReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\AdminNotFoundException::class);

        $this->resource->put('page://self/admin/member', [
            'loginId' => 'no-such-admin',
            'name' => 'irrelevant',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
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
        $this->expectException(\MyVendor\BeMart\Be\Exception\InsufficientAuthorityException::class);

        $this->resource->delete('page://self/admin/member', [
            'loginId' => 'test-admin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnDeleteAlreadyDeletedReturns200WithFlag(): void
    {
        // Fake context is static-fixture based; replay-after-mutation is
        // covered by the SQL suite. This fixture directly exercises the
        // idempotent already-deleted branch.
        $ro = $this->resource->delete('page://self/admin/member', [
            'loginId' => self::DELETED_LOGIN_ID,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertTrue($ro->body['alreadyDeleted']);
        $this->assertStringContainsString('既に削除', $ro->body['message']);
    }
}
