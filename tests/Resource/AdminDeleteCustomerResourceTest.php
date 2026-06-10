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
    private const WITHDRAWN_ID = '30000000aaaa3333bbbb4444cccc5555';

    private ResourceInterface $resource;
    private Injector $injector;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    /**
     * Build a fresh resource client with the given admin session
     * adminId (null = admin-anonymous). Same pattern as
     * AdminCustomerResourceTest — rebinds AdminSession so the
     * admin firewall can be flipped per-test.
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

    public function testOnPostAlreadyDeletedReturns200WithAlreadyDeletedFlag(): void
    {
        // Fake context is static-fixture based; replay-after-mutation is
        // covered by the SQL suite. This fixture directly exercises the
        // idempotent already-deleted branch.
        $mailer = $this->injector->getInstance(FakeMailer::class);
        $mailCountBefore = count($mailer->withdrawConfirmations);

        $ro = $this->resource->post('page://self/admin/delete-customer', [
            'customerId' => self::WITHDRAWN_ID,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertTrue($ro->body['alreadyDeleted']);
        $this->assertStringContainsString('既に削除', $ro->body['message']);
        $this->assertCount(
            $mailCountBefore,
            $mailer->withdrawConfirmations,
            'Already-deleted branch must NOT send a withdrawal mail.',
        );
    }

    public function testOnPostUnknownCustomerIdReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\CustomerNotFoundException::class);

        $this->resource->post('page://self/admin/delete-customer', [
            'customerId' => 'nonexistent-customer-id-zzzz9999',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->post('page://self/admin/delete-customer', [
            'customerId' => self::ALICE_ID,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

}
