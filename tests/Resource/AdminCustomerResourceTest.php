<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-layer coverage for goCustomer.
 *
 * Mirrors AdminLogoutResourceTest's `rebindAdminSession` helper.
 * `alice@example.com` is the happy-path target (pre-seeded full
 * profile in var/fake/customers.json); 403 / 404 / 400 are driven by
 * rebinding the admin session or perturbing the email.
 */
final class AdminCustomerResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    /**
     * Build a fresh resource client with the given admin session
     * adminId (null = admin-anonymous). Same pattern as
     * AdminLogoutResourceTest — rebinds AdminSession rather
     * than CustomerSession because admin and customer are parallel
     * firewalls (Wave 4 decision).
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

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetHappyPathReturns200(): void
    {
        $ro = $this->resource->get('page://self/admin/customer', [
            'email' => 'alice@example.com',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('0123456789abcdef0123456789abcdef', $ro->body['customerId']);
        $this->assertSame('alice@example.com', $ro->body['email']);
        $this->assertSame('山田', $ro->body['name01']);
        $this->assertSame('アリス', $ro->body['name02']);
        // Admin sees richer fields than goMypage (birth, sex, job,
        // full address). Verify a sampling of the admin-only surface.
        $this->assertSame('1990-04-01', $ro->body['birth']);
        $this->assertSame(2, $ro->body['sex']);
        $this->assertSame(7, $ro->body['job']);
        $this->assertSame('渋谷区', $ro->body['addr01']);
        $this->assertSame('神宮前1-1-1', $ro->body['addr02']);
        $this->assertSame(0, $ro->body['initialPoint']);
        // Aggregates carry empty-list shape rather than null.
        $this->assertSame([], $ro->body['orders']);
        $this->assertSame(0, $ro->body['orderCount']);
        $this->assertSame(0, $ro->body['totalSpent']);
        $this->assertSame([], $ro->body['favorites']);
        $this->assertSame(0, $ro->body['favoriteCount']);
    }

    public function testOnGetNoAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->get('page://self/admin/customer', [
            'email' => 'alice@example.com',
        ]);
    }

    public function testOnGetUnknownEmailReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\CustomerNotFoundException::class);

        $this->resource->get('page://self/admin/customer', [
            'email' => 'nosuch@example.com',
        ]);
    }

    public function testOnGetBadEmailFormatReturns400(): void
    {
        $ro = $this->resource->get('page://self/admin/customer', [
            'email' => 'not-an-email',
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }
}
