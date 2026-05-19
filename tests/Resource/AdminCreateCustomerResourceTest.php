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

final class AdminCreateCustomerResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    /**
     * Build a fresh resource client with the given admin session
     * adminId (null = admin-anonymous). Mirrors the
     * AdminLogoutResourceTest helper.
     */
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

    public function testOnPostCreatesActiveCustomerAndReturns201(): void
    {
        $ro = $this->resource->post('page://self/admin/create-customer', [
            'email' => 'admin-created-resource@example.com',
            'password' => 'admin-set-passphrase-2026',
            'name01' => '管理',
            'name02' => '太郎',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('admin-created-resource@example.com', $ro->body['email']);
        $this->assertSame('管理', $ro->body['name01']);
        $this->assertSame(100, $ro->body['initialPoint']);
        // ALPS doc: status=2 (Active) immediately.
        $this->assertSame(2, $ro->body['customerStatus']);
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{32}\z/', $ro->body['customerId']);
        $this->assertArrayHasKey('Location', $ro->headers);
        $this->assertStringContainsString(
            'email=admin-created-resource%40example.com',
            $ro->headers['Location'],
        );
    }

    public function testOnPostDuplicateEmailReturns409(): void
    {
        $ro = $this->resource->post('page://self/admin/create-customer', [
            'email' => 'alice@example.com',
            'password' => 'admin-overwrite-attempt-2026',
            'name01' => '別人',
            'name02' => 'A',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(409, $ro->code);
        $this->assertSame('alice@example.com', $ro->body['email']);
    }

    public function testOnPostAnonymousAdminReturns403(): void
    {
        // Admin AUTHZ check happens inside the first Being. With no
        // admin session, the chain raises UnauthorizedAdminAccessException
        // which the resource maps to 403.
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/create-customer', [
            'email' => 'no-admin@example.com',
            'password' => 'no-admin-passphrase-2026',
            'name01' => '無権限',
            'name02' => '次郎',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/create-customer', [
            'email' => 'no-csrf@example.com',
            'password' => 'no-csrf-passphrase-2026',
            'name01' => '佐藤',
            'name02' => '七郎',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPostInvalidEmailReturns400(): void
    {
        $ro = $this->resource->post('page://self/admin/create-customer', [
            'email' => 'not-an-email',
            'password' => 'whatever-2026',
            'name01' => '佐藤',
            'name02' => '五郎',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
        $this->assertNotEmpty($ro->body['message']);
    }
}
