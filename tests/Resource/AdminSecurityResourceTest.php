<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Form\AdminSecurityForm;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-layer coverage for the admin セキュリティ管理 Tier-2 page.
 *
 * The resource is a thin GET renderer for the EC-CUBE security config
 * screen: BeMart does not yet model config-file writes as Be
 * transitions, so the page exposes the current default config body
 * shape only. The AUTHZ guard rejects anonymous admins with 403.
 */
final class AdminSecurityResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsFormAndConfigShape(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->get('page://self/admin/security');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminSecurityForm::class, $ro->body['form']);
        $this->assertFalse($ro->body['isSecureRequest']);
    }

    public function testOnGetAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/security');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }

    public function testOnPutUpdatesSettings(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->put('page://self/admin/security', [
            'adminAllowHosts' => '192.168.0.0/24',
            'trustedHosts' => '^example\\.com$',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doUpdateSecurity', $ro->body['transitionId']);
    }

    public function testOnPutMissingCsrfReturns403(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->put('page://self/admin/security', [
            'adminAllowHosts' => '192.168.0.0/24',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnPutAnonymousReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->put('page://self/admin/security', [
            'adminAllowHosts' => '192.168.0.0/24',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
