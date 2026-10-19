<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Auth;

use BEAR\Csrf\CsrfSessionKey;
use BEAR\Csrf\CsrfTokenInterface;
use BEAR\Csrf\Http\RequestTokenInterface;
use BEAR\Csrf\StoredCsrfToken;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Auth\HtmlCartSessionPrefix;
use MyVendor\BeMart\Auth\HtmlSessionAdapter;
use MyVendor\BeMart\Auth\NullSessionStarter;
use MyVendor\BeMart\Auth\SessionStarterCsrfStore;
use MyVendor\BeMart\Tests\Fake\Csrf\FakeRequestToken;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use Override;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

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

final class HtmlSessionAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        unset(
            $_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY],
            $_SESSION[CsrfSessionKey::DEFAULT_NAME],
        );
    }

    protected function tearDown(): void
    {
        unset(
            $_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY],
            $_SESSION[CsrfSessionKey::DEFAULT_NAME],
        );
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
            session_write_close();
            session_id('');
        }
    }

    public function testReturnsCustomerIdFromSession(): void
    {
        $_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY] = 'customer-001';

        $adapter = new HtmlSessionAdapter();

        $this->assertSame('customer-001', $adapter->customerId);
    }

    public function testReturnsNullWhenSessionKeyAbsent(): void
    {
        $adapter = new HtmlSessionAdapter();

        $this->assertNull($adapter->customerId);
    }

    public function testEmptyStringSessionValueTreatedAsAnonymous(): void
    {
        $_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY] = '';

        $adapter = new HtmlSessionAdapter();

        $this->assertNull($adapter->customerId);
    }

    public function testNonStringSessionValueTreatedAsAnonymous(): void
    {
        $_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY] = 123;

        $adapter = new HtmlSessionAdapter();

        $this->assertNull($adapter->customerId);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testHtmlContextLoginWritesCustomerIdToSession(): void
    {
        $this->startActiveSession();

        $ro = $this->htmlResource()->post('page://self/login', [
            'email' => 'login-test@example.com',
            'password' => 'local-dev-member-password',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('10000000aaaa1111bbbb2222cccc3333', $_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY] ?? null);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testHtmlContextLogoutClearsCustomerIdAndRedirectsHome(): void
    {
        $this->startActiveSession();
        $_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY] = '10000000aaaa1111bbbb2222cccc3333';

        $ro = $this->htmlResource()->post('page://self/logout');

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertArrayNotHasKey(HtmlSessionAdapter::CUSTOMER_ID_KEY, $_SESSION);
        $this->assertSame('/', $ro->headers['Location']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testHtmlContextLoginRegeneratesSessionIdAndRetiresTheAnonymousCsrfToken(): void
    {
        $this->startActiveSession();
        $sessionIdBeforeLogin = session_id();
        // Pinned while anonymous: the cart rows keyed by this prefix must stay
        // reachable after the id rotates.
        $cartPrefix = (new HtmlCartSessionPrefix())->prefix();
        $csrf = $this->sessionCsrfToken();
        $anonymousToken = $csrf->issue();

        $ro = $this->htmlResourceSubmitting($csrf, $anonymousToken)->post('page://self/login', [
            'email' => 'login-test@example.com',
            'password' => 'local-dev-member-password',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($sessionIdBeforeLogin, $cartPrefix);
        $this->assertNotSame($sessionIdBeforeLogin, session_id());
        $this->assertFalse($csrf->verify($anonymousToken));
        $this->assertNotSame($anonymousToken, $csrf->issue());
        $this->assertSame($cartPrefix, (new HtmlCartSessionPrefix())->prefix());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testHtmlContextLogoutRetiresSessionIdAndCsrfToken(): void
    {
        $this->startActiveSession();
        $_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY] = '10000000aaaa1111bbbb2222cccc3333';
        $sessionIdBeforeLogout = session_id();
        $csrf = $this->sessionCsrfToken();
        $tokenBeforeLogout = $csrf->issue();

        $this->htmlResourceSubmitting($csrf, $tokenBeforeLogout)->post('page://self/logout');

        $this->assertNotSame($sessionIdBeforeLogout, session_id());
        $this->assertFalse($csrf->verify($tokenBeforeLogout));
    }

    private function htmlResource(): ResourceInterface
    {
        return HtmlTestInjector::getInstance()->getInstance(ResourceInterface::class);
    }

    /** The production token over the session this test opened, in place of FakeModule's fixed one. */
    private function sessionCsrfToken(): CsrfTokenInterface
    {
        return new StoredCsrfToken(new SessionStarterCsrfStore(new NullSessionStarter()));
    }

    private function htmlResourceSubmitting(CsrfTokenInterface $csrf, string $submitted): ResourceInterface
    {
        $module = new class ($csrf, $submitted) extends AbstractModule {
            public function __construct(
                private readonly CsrfTokenInterface $csrf,
                private readonly string $submitted,
            ) {
                parent::__construct();
            }

            #[Override]
            protected function configure(): void
            {
                $this->bind(CsrfTokenInterface::class)->toInstance($this->csrf);
                $this->bind(RequestTokenInterface::class)->toInstance(new FakeRequestToken($this->submitted));
            }
        };

        return HtmlTestInjector::getOverrideInstance($module)->getInstance(ResourceInterface::class);
    }

    private function startActiveSession(): void
    {
        session_cache_limiter('');
        session_save_path(sys_get_temp_dir());
        session_id('bemart-customer-' . uniqid());
        session_start(['use_cookies' => false, 'use_strict_mode' => false]);
    }
}
