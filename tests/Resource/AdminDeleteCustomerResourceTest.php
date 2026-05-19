<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Service\FakeMailer;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function count;
use function dirname;

/**
 * Resource-layer coverage for doDeleteCustomer.
 *
 * Mirrors AdminCustomerResourceTest's `rebindAdminSession` helper.
 * `alice@example.com` (customerId 0123...cdef) is the happy-path target
 * (pre-seeded in var/fake/customers.json); 403 / 404 are driven by
 * rebinding the admin session or perturbing the customerId.
 */
final class AdminDeleteCustomerResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const ALICE_EMAIL = 'alice@example.com';

    private ResourceInterface $resource;
    private Injector $injector;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    /**
     * Build a fresh resource client with the given admin session
     * adminId (null = admin-anonymous). Same pattern as
     * AdminCustomerResourceTest — rebinds AdminSessionInterface so the
     * admin firewall can be flipped per-test.
     */
    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $this->injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $this->injector->getInstance(ResourceInterface::class);
    }

    public function testOnPostHappyPathReturns200(): void
    {
        $ro = $this->resource->post('page://self/admin/delete-customer', [
            'customerId' => self::ALICE_ID,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertSame(self::ALICE_EMAIL, $ro->body['originalEmail']);
        $this->assertFalse($ro->body['alreadyDeleted']);
        $this->assertStringContainsString('削除', $ro->body['message']);
    }

    public function testOnPostReDeleteReturns200WithAlreadyDeletedFlag(): void
    {
        // First delete — fresh.
        $this->resource->post('page://self/admin/delete-customer', [
            'customerId' => self::ALICE_ID,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $mailer = $this->injector->getInstance(FakeMailer::class);
        $mailCountAfterFirst = count($mailer->withdrawConfirmations());

        // Second delete — replay. The customer is already in status=3,
        // so the Final short-circuits.
        $ro = $this->resource->post('page://self/admin/delete-customer', [
            'customerId' => self::ALICE_ID,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertTrue($ro->body['alreadyDeleted']);
        $this->assertStringContainsString('既に削除', $ro->body['message']);
        $this->assertCount(
            $mailCountAfterFirst,
            $mailer->withdrawConfirmations(),
            'Idempotent replay must NOT send a second withdrawal mail.',
        );
    }

    public function testOnPostUnknownCustomerIdReturns404(): void
    {
        $ro = $this->resource->post('page://self/admin/delete-customer', [
            'customerId' => 'nonexistent-customer-id-zzzz9999',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertStringContainsString('会員', $ro->body['message']);
    }

    public function testOnPostAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/delete-customer', [
            'customerId' => self::ALICE_ID,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
        // Anti-enumeration: an admin-anonymous caller MUST NOT learn
        // whether the queried customerId resolves.
        $this->assertArrayNotHasKey('customerId', $ro->body);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/delete-customer', [
            'customerId' => self::ALICE_ID,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }
}
