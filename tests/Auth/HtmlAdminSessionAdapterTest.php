<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Auth;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Auth\AdminTwoFactorChallenge;
use MyVendor\BeMart\Auth\HtmlAdminLoginChallengeAdapter;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeTwoFactorAuth;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

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
    protected function setUp(): void
    {
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

        $ro = $this->htmlResource()->post('page://self/admin/login', [
            'loginId' => 'test-admin',
            'password' => 'local-dev-admin-password',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        // Browser (HTML context) Post/Redirect/Get: the password step
        // redirects to the 2FA challenge with 303 so the browser follows it.
        // (Resource clients keep 200 + body — see AdminLoginResourceTest.)
        $this->assertSame(Code::SEE_OTHER, $ro->code);
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
        $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY] = 'ad000000000000000000000000000001';

        $ro = $this->htmlResource()->post('page://self/admin/logout', [
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertArrayNotHasKey(HtmlAdminSessionAdapter::ADMIN_ID_KEY, $_SESSION);
        $this->assertSame('/admin/login', $ro->headers['Location']);
    }

    /**
     * Browser Post/Redirect/Get for first-device 2FA setup: registering the
     * device completes the login flow and must redirect (303) into the admin
     * dashboard so the browser follows it. A 200 here leaves the browser
     * stranded on the setup form (regression guard for the admin-login fix).
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testHtmlContextTwoFactorSetupRedirectsToAdminHome(): void
    {
        $this->startActiveSession();
        $challenge = new HtmlAdminLoginChallengeAdapter();
        $challenge->startSetup(
            'ad000000000000000000000000000001',
            'test-admin',
            FakeTwoFactorAuth::FIXED_SECRET,
        );

        $ro = $this->htmlResource()->put('page://self/admin/two-factor-auth-set', [
            'deviceToken' => FakeTwoFactorAuth::VALID_TOKEN,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/admin/index', $ro->headers['Location']);
    }

    /**
     * Browser Post/Redirect/Get for the returning-device 2FA challenge: a
     * verified code completes the login flow and must redirect (303) into the
     * admin dashboard. Mirrors the setup case above for subsequent logins.
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testHtmlContextTwoFactorChallengeRedirectsToAdminHome(): void
    {
        $this->startActiveSession();
        $challenge = new HtmlAdminLoginChallengeAdapter();
        $challenge->startVerification('ad000000000000000000000000000001', 'test-admin');

        $ro = $this->htmlResource()->post('page://self/admin/two-factor-auth', [
            'deviceToken' => FakeTwoFactorAuth::VALID_TOKEN,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/admin/index', $ro->headers['Location']);
    }

    private function htmlResource(): ResourceInterface
    {
        $injector = HtmlTestInjector::getInstance();

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
