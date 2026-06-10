<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeMailer;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-layer coverage for doResendActivationMail (Phase 3
 * ALPS-audit remediation).
 *
 * Mirrors AdminDeleteCustomerResourceTest's `rebindAdminSession` helper.
 * `provisional@example.com` (customerStatus = 1, carries a secretKey) is
 * the happy-path 仮会員 target pre-seeded in be/var/fake/customers.json;
 * `alice@example.com` is an active member used for the 409 case.
 */
final class AdminResendActivationMailResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const PROVISIONAL_EMAIL = 'provisional@example.com';
    private const PROVISIONAL_ID = '20000000dddd2222eeee3333ffff4444';
    private const ACTIVE_EMAIL = 'alice@example.com';
    private const URI = 'page://self/admin/customer/resend-activation-mail';

    private ResourceInterface $resource;
    private Injector $injector;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    /**
     * Build a fresh resource client with the given admin session
     * adminId (null = admin-anonymous). Rebinds AdminSession
     * so the admin firewall can be flipped per-test — same pattern as
     * AdminDeleteCustomerResourceTest.
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

        $this->injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $this->injector->getInstance(ResourceInterface::class);
    }

    public function testOnPostHappyPathReturns200(): void
    {
        $ro = $this->resource->post(self::URI, [
            'email' => self::PROVISIONAL_EMAIL,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::PROVISIONAL_ID, $ro->body['customerId']);
        $this->assertSame(self::PROVISIONAL_EMAIL, $ro->body['email']);
        $this->assertStringContainsString('認証メール', $ro->body['message']);

        $mailer = $this->injector->getInstance(FakeMailer::class);
        $this->assertCount(1, $mailer->customerActivations);
        $this->assertSame(
            self::PROVISIONAL_EMAIL,
            $mailer->customerActivations[0]['email'],
        );
    }

    public function testOnPostAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->post(self::URI, [
            'email' => self::PROVISIONAL_EMAIL,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostUnknownEmailReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\CustomerNotFoundException::class);

        $this->resource->post(self::URI, [
            'email' => 'nobody@example.com',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostAlreadyActiveCustomerReturns409(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\CustomerAlreadyActivatedException::class);

        $this->resource->post(self::URI, [
            'email' => self::ACTIVE_EMAIL,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }
}
