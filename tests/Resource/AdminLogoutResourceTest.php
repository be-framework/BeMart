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

final class AdminLogoutResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    /**
     * Build a fresh resource client with the given admin session
     * adminId (null = admin-anonymous). Mirrors the customer-side
     * `rebindSession` helper in ChangeResourceTest / LogoutResourceTest,
     * but rebinds AdminSession instead of CustomerSession —
     * because admin and customer are parallel firewalls (Wave 4
     * decision: two AAA principal classes, two interfaces).
     */
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

    public function testOnPostLogsOutLoggedInAdmin(): void
    {
        $ro = $this->resource->post('page://self/admin/logout', [
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertTrue($ro->body['wasLoggedIn']);
        $this->assertSame(self::TEST_ADMIN_ID, $ro->body['adminId']);
        $this->assertStringContainsString('ログアウト', $ro->body['message']);
    }

    public function testOnPostIsIdempotentForAnonymous(): void
    {
        // ALPS type=idempotent: logging out an admin-anonymous client is
        // a no-op success, NOT a 401. The body simply reports
        // wasLoggedIn=false.
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/logout', [
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['wasLoggedIn']);
        $this->assertNull($ro->body['adminId']);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/logout', []);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }
}
