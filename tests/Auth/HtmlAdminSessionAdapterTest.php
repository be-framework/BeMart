<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Auth;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Auth\AdminTwoFactorChallenge;
use MyVendor\BeMart\Auth\HtmlAdminLoginChallengeAdapter;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\HtmlTestModule;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;
use function getenv;
use function putenv;
use function session_cache_limiter;
use function session_destroy;
use function session_id;
use function session_save_path;
use function session_start;
use function session_status;
use function session_write_close;
use function sys_get_temp_dir;
use function uniqid;

use const PHP_SESSION_ACTIVE;

final class HtmlAdminSessionAdapterTest extends TestCase
{
    private string|false $appContextBefore;

    protected function setUp(): void
    {
        $this->appContextBefore = getenv('APP_CONTEXT');
        unset(
            $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY],
            $_SESSION[HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY],
            $_SESSION[HtmlAdminLoginChallengeAdapter::SETUP_CHALLENGE_KEY],
        );
    }

    protected function tearDown(): void
    {
        unset(
            $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY],
            $_SESSION[HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY],
            $_SESSION[HtmlAdminLoginChallengeAdapter::SETUP_CHALLENGE_KEY],
        );
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
            session_write_close();
            session_id('');
        }

        if ($this->appContextBefore === false) {
            putenv('APP_CONTEXT');

            return;
        }

        putenv('APP_CONTEXT=' . $this->appContextBefore);
    }

    public function testReturnsAdminIdFromSession(): void
    {
        $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY] = 'admin-001';

        $adapter = new HtmlAdminSessionAdapter();

        $this->assertSame('admin-001', $adapter->adminId);
    }

    public function testReturnsNullWhenSessionKeyAbsent(): void
    {
        $adapter = new HtmlAdminSessionAdapter();

        $this->assertNull($adapter->adminId);
    }

    public function testEmptyStringSessionValueTreatedAsAnonymous(): void
    {
        $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY] = '';

        $adapter = new HtmlAdminSessionAdapter();

        $this->assertNull($adapter->adminId);
    }

    public function testNonStringSessionValueTreatedAsAnonymous(): void
    {
        $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY] = 123;

        $adapter = new HtmlAdminSessionAdapter();

        $this->assertNull($adapter->adminId);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testHtmlContextAdminLoginStartsTwoFactorChallengeWithoutElevatingSession(): void
    {
        $this->startActiveSession();
        $sessionIdBeforeLogin = session_id();
        putenv('APP_CONTEXT=html-test-hal-api-app');

        $ro = $this->htmlResource()->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => 'local-dev-admin-password',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertNotSame($sessionIdBeforeLogin, session_id());
        $this->assertSame('/admin/two-factor-auth', $ro->headers['Location']);
        $this->assertArrayNotHasKey(HtmlAdminSessionAdapter::ADMIN_ID_KEY, $_SESSION);
        $this->assertSame(
            [
                'adminId' => 'ad000000000000000000000000000001',
                'loginId' => 'test-admin',
            ],
            $_SESSION[HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY] ?? null,
        );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCompletingTwoFactorChallengeRotatesSessionIdBeforeElevation(): void
    {
        $this->startActiveSession();
        $sessionIdBeforeChallenge = session_id();
        $adapter = new HtmlAdminLoginChallengeAdapter();
        $challenge = new AdminTwoFactorChallenge(
            adminId: 'ad000000000000000000000000000001',
            loginId: 'test-admin',
        );

        $adapter->startVerification($challenge->adminId, $challenge->loginId);
        $adapter->completeVerification($challenge);

        $this->assertNotSame($sessionIdBeforeChallenge, session_id());
        $this->assertSame($challenge->adminId, $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY] ?? null);
        $this->assertArrayNotHasKey(HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY, $_SESSION);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testHtmlContextAdminLogoutClearsAdminIdAndRedirectsToLogin(): void
    {
        $this->startActiveSession();
        putenv('APP_CONTEXT=html-test-hal-api-app');
        $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY] = 'ad000000000000000000000000000001';

        $ro = $this->htmlResource()->post('page://self/admin/logout', [
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertArrayNotHasKey(HtmlAdminSessionAdapter::ADMIN_ID_KEY, $_SESSION);
        $this->assertSame('/admin/login', $ro->headers['Location']);
    }

    private function htmlResource(): ResourceInterface
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $injector = new Injector(
            new HtmlTestModule($meta),
            dirname(__DIR__, 2) . '/var/tmp/html',
        );

        return $injector->getInstance(ResourceInterface::class);
    }

    private function startActiveSession(): void
    {
        session_cache_limiter('');
        session_save_path(sys_get_temp_dir());
        session_id('bemart-admin-' . uniqid());
        session_start(['use_cookies' => false, 'use_strict_mode' => false]);
    }
}
