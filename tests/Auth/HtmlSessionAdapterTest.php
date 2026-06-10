<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Auth;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Auth\HtmlSessionAdapter;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
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

final class HtmlSessionAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        unset($_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY]);
    }

    protected function tearDown(): void
    {
        unset($_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY]);
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
            'password' => 'login-test-password-2026',
            'csrfToken' => FakeCsrfToken::TOKEN,
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

        $ro = $this->htmlResource()->post('page://self/logout', [
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertArrayNotHasKey(HtmlSessionAdapter::CUSTOMER_ID_KEY, $_SESSION);
        $this->assertSame('/', $ro->headers['Location']);
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
        session_id('bemart-customer-' . uniqid());
        session_start(['use_cookies' => false, 'use_strict_mode' => false]);
    }
}
