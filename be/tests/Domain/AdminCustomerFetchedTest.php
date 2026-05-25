<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\CustomerNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminCustomerFetched;
use MyVendor\BeMart\Be\Input\GetAdminCustomerInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Wave 5 (goCustomer) — Direct safe-read with cross-firewall AUTHZ.
 *
 * Reads `alice@example.com` from var/fake/customers.json (full profile
 * pre-seeded). Order history / favorites are empty for alice in the
 * default fixtures — the projection still returns valid empty lists
 * with `orderCount=0` / `favoriteCount=0` / `totalSpent=0`. The 403 /
 * 404 branches are driven by rebinding AdminSessionInterface (mirrors
 * the Wave 4 AdminLogoutResourceTest helper).
 */
final class AdminCustomerFetchedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    /**
     * Build a fresh Becoming with the given admin session adminId (null
     * = admin-anonymous). Same pattern as AdminLogoutResourceTest, but
     * resolves BecomingInterface for direct-domain assertions rather
     * than ResourceInterface for HTTP-shape assertions.
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
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathReturnsCustomerProfile(): void
    {
        $final = ($this->becoming)(new GetAdminCustomerInput('alice@example.com'));

        $this->assertInstanceOf(AdminCustomerFetched::class, $final);
        $this->assertSame('0123456789abcdef0123456789abcdef', $final->customerId);
        $this->assertSame('alice@example.com', $final->email);
        $this->assertSame('山田', $final->name01);
        $this->assertSame('アリス', $final->name02);
        $this->assertSame('ヤマダ', $final->kana01);
        $this->assertSame('アリス', $final->kana02);
        $this->assertNull($final->companyName);
        $this->assertSame('0312345678', $final->phoneNumber);
        $this->assertSame('1500001', $final->postalCode);
        $this->assertSame(13, $final->pref);
        $this->assertSame('渋谷区', $final->addr01);
        $this->assertSame('神宮前1-1-1', $final->addr02);
        $this->assertSame('1990-04-01', $final->birth);
        $this->assertSame(2, $final->sex);
        $this->assertSame(7, $final->job);
        $this->assertSame(2, $final->customerStatus);
        $this->assertSame(0, $final->initialPoint);

        // Alice has no seeded orders / favorites — the projection is a
        // valid empty list rather than null. Aggregate counters track
        // the empty state.
        $this->assertSame([], $final->orders);
        $this->assertSame(0, $final->orderCount);
        $this->assertSame(0, $final->totalSpent);
        $this->assertSame([], $final->favorites);
        $this->assertSame(0, $final->favoriteCount);
    }

    public function testNoAdminSessionRaisesUnauthorizedAdminAccess(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new GetAdminCustomerInput('alice@example.com'));
    }

    public function testUnknownEmailRaisesCustomerNotFound(): void
    {
        $this->expectException(CustomerNotFoundException::class);
        ($this->becoming)(new GetAdminCustomerInput('nosuch@example.com'));
    }
}
