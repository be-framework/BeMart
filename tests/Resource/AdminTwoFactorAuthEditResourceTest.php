<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminTwoFactorAuthForm;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-layer coverage for the admin 2段階認証設定 Tier-2 page.
 *
 * The admin-authenticated variant of the 2FA setup renderer. TOTP
 * verification is not an ALPS transition in this repo, so the resource
 * serves the GET page and form body only. The AUTHZ guard rejects
 * anonymous admins with 403; an authenticated admin sees their own id
 * as `memberName`.
 */
final class AdminTwoFactorAuthEditResourceTest extends TestCase
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
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        });

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsFormAndMemberName(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->get('page://self/admin/two-factor-auth-edit');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminTwoFactorAuthForm::class, $ro->body['form']);
        $this->assertSame(self::TEST_ADMIN_ID, $ro->body['memberName']);
        $this->assertSame('BeMart', $ro->body['shopName']);
    }

    public function testOnGetAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/two-factor-auth-edit');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
