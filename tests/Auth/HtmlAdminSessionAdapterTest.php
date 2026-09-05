<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Auth;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Auth\AdminTwoFactorChallenge;
use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
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
            $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY],
        );
    }

    protected function tearDown(): void
    {
        unset(
            $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY],
            $_SESSION[HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY],
            $_SESSION[HtmlAdminLoginChallengeAdapter::SETUP_CHALLENGE_KEY],
            $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY],
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

        // HTML context: the formSubmission port treats every mutation POST as
        // a browser form, so a successful login answers 303 (the body and
        // challenge semantics below are unchanged).
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

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAdminLogoutDropsTheChallengeStateThatWouldReElevateTheSession(): void
    {
        $this->startActiveSession();
        $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY] = 'ad000000000000000000000000000001';
        $resource = $this->htmlResource();
        // A logged-in admin visiting the challenge page re-seeds a verify
        // challenge from the admin session, so logout has to retire the
        // challenge too: it elevates admin_id again with no password check.
        $resource->get('page://self/admin/two-factor-auth');

        $resource->post('page://self/admin/logout', ['csrfToken' => FakeCsrfToken::TOKEN]);
        $reElevation = $resource->post('page://self/admin/two-factor-auth', [
            'deviceToken' => FakeTwoFactorAuth::VALID_TOKEN,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $reElevation->code);
        $this->assertArrayNotHasKey(HtmlAdminSessionAdapter::ADMIN_ID_KEY, $_SESSION);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAdminLogoutRetiresSessionIdAndCsrfReference(): void
    {
        $this->startActiveSession();
        $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY] = 'ad000000000000000000000000000001';
        $sessionIdBeforeLogout = session_id();
        $tokenBeforeLogout = (new EccubeSharedCsrfTokenAdapter())->token;

        $this->htmlResource()->post('page://self/admin/logout', ['csrfToken' => FakeCsrfToken::TOKEN]);

        $this->assertNotSame($sessionIdBeforeLogout, session_id());
        $this->assertFalse((new EccubeSharedCsrfTokenAdapter())->isValid($tokenBeforeLogout));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCompletingTwoFactorChallengeRetiresTheAnonymousSessionId(): void
    {
        // The planted-cookie defence: elevation must not keep the session id
        // the client held while unauthenticated. The CSRF reference is retired
        // by the writer at login and logout, not here.
        $this->startActiveSession();
        $anonymousSessionId = session_id();
        $adapter = new HtmlAdminLoginChallengeAdapter();
        $challenge = new AdminTwoFactorChallenge(
            adminId: 'ad000000000000000000000000000001',
            loginId: 'test-admin',
        );

        $adapter->startVerification($challenge->adminId, $challenge->loginId);
        $adapter->completeVerification($challenge);

        $this->assertNotSame($anonymousSessionId, session_id());
        $this->assertSame($challenge->adminId, $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY] ?? null);
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
