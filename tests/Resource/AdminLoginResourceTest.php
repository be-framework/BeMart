<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Auth\HtmlAdminLoginChallengeAdapter;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeTwoFactorAuth;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class AdminLoginResourceTest extends TestCase
{
    private ResourceInterface $resource;
    private FakeTwoFactorAuth $twoFactorAuth;

    protected function setUp(): void
    {
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(CsrfToken::class)->to(FakeCsrfToken::class);
            }
        });

        $injector = new Injector(
            $base,
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->twoFactorAuth = $injector->getInstance(FakeTwoFactorAuth::class);
        unset(
            $_SESSION[HtmlAdminLoginChallengeAdapter::SETUP_CHALLENGE_KEY],
            $_SESSION[HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY],
            $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY],
        );
    }

    protected function tearDown(): void
    {
        unset(
            $_SESSION[HtmlAdminLoginChallengeAdapter::SETUP_CHALLENGE_KEY],
            $_SESSION[HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY],
            $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY],
        );
    }

    public function testOnPostAuthenticatesAndRedirectsToTwoFactorAuth(): void
    {
        $ro = $this->resource->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => 'admin-test-password-2026',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('ad000000000000000000000000000001', $ro->body['adminId']);
        $this->assertSame('test-admin', $ro->body['loginId']);
        $this->assertSame('テスト管理者', $ro->body['name']);
        $this->assertSame(0, $ro->body['authority']);
        $this->assertSame('/admin/two-factor-auth', $ro->headers['Location']);
        $this->assertArrayNotHasKey(HtmlAdminSessionAdapter::ADMIN_ID_KEY, $_SESSION);
        $this->assertSame('test-admin', $_SESSION[HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY]['loginId'] ?? null);
    }

    public function testOnPostStartsSetupChallengeWhenDeviceIsMissing(): void
    {
        $this->twoFactorAuth->secrets = [];

        $ro = $this->resource->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => 'admin-test-password-2026',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/admin/two-factor-auth-set', $ro->headers['Location']);
        $this->assertSame('test-admin', $_SESSION[HtmlAdminLoginChallengeAdapter::SETUP_CHALLENGE_KEY]['loginId'] ?? null);
        $this->assertSame(FakeTwoFactorAuth::FIXED_SECRET, $_SESSION[HtmlAdminLoginChallengeAdapter::SETUP_CHALLENGE_KEY]['authKey'] ?? null);
    }

    public function testOnPostWrongPasswordReturns401(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\AdminLoginFailedException::class);

        $this->resource->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => 'not-the-right-password',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => 'admin-test-password-2026',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPostShortPasswordReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => 'short',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }
}
