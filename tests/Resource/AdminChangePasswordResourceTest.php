<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-layer coverage for the admin パスワード変更 page
 * (doChangePassword). GET renders the form; POST drives the Be
 * transition with the credential/CSRF/session boundary.
 */
final class AdminChangePasswordResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const CURRENT_PASSWORD = 'admin-test-password-2026';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

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

    public function testOnGetRendersForm(): void
    {
        $ro = $this->resource->get('page://self/admin/change-password');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertArrayHasKey('form', $ro->body);
    }

    public function testOnPostChangesPassword(): void
    {
        $ro = $this->resource->post('page://self/admin/change-password', [
            'currentPassword' => self::CURRENT_PASSWORD,
            'changePasswordFirst' => 'new-strong-password-2026',
            'changePasswordSecond' => 'new-strong-password-2026',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doChangePassword', $ro->body['transitionId']);
        $this->assertSame('test-admin', $ro->body['loginId']);
        $this->assertArrayHasKey('Location', $ro->headers);
    }

    public function testOnPostWrongCurrentPasswordReturns400(): void
    {
        $ro = $this->resource->post('page://self/admin/change-password', [
            'currentPassword' => 'wrong-password',
            'changePasswordFirst' => 'new-strong-password-2026',
            'changePasswordSecond' => 'new-strong-password-2026',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnPostConfirmationMismatchReturns400(): void
    {
        $ro = $this->resource->post('page://self/admin/change-password', [
            'currentPassword' => self::CURRENT_PASSWORD,
            'changePasswordFirst' => 'new-strong-password-2026',
            'changePasswordSecond' => 'mismatch-confirmation-2026',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/change-password', [
            'currentPassword' => self::CURRENT_PASSWORD,
            'changePasswordFirst' => 'new-strong-password-2026',
            'changePasswordSecond' => 'new-strong-password-2026',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPostAnonymousReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/change-password', [
            'currentPassword' => self::CURRENT_PASSWORD,
            'changePasswordFirst' => 'new-strong-password-2026',
            'changePasswordSecond' => 'new-strong-password-2026',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
