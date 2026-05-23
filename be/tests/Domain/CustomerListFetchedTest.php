<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\CustomerListFetched;
use MyVendor\BeMart\Be\Input\GetCustomerListInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function array_column;
use function dirname;

/**
 * Wave 5 (goCustomerList) — admin-side filter search Final.
 *
 * Reuses the customer fixture from `var/fake/customers.json` (5 rows:
 * alice / bob / carol / login-test / provisional). The admin session
 * is rebound per-case to drive happy / forbidden branches — same
 * pattern as the Wave 4 AdminLogoutResourceTest but exercised at the
 * Becoming layer instead of the BEAR layer.
 */
final class CustomerListFetchedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    /**
     * Build a Becoming with the given admin session adminId (null =
     * admin-anonymous). Mirrors the customer-side `rebindSession`
     * helper but rebinds AdminSessionInterface — Wave 4 decision:
     * admin and customer are parallel firewalls.
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

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathListsAllFixtureCustomers(): void
    {
        $final = ($this->becoming)(new GetCustomerListInput());

        $this->assertInstanceOf(CustomerListFetched::class, $final);
        // Seed corpus: alice + bob + carol + login-test + provisional.
        $this->assertSame(5, $final->count);
        $emails = array_column($final->customers, 'email');
        $this->assertContains('alice@example.com', $emails);
        $this->assertContains('bob@example.com', $emails);
        $this->assertContains('carol@example.com', $emails);
        $this->assertContains('login-test@example.com', $emails);
        $this->assertContains('provisional@example.com', $emails);

        // Projection is shallow — no passwordHash / secretKey leakage.
        foreach ($final->customers as $row) {
            $this->assertArrayNotHasKey('passwordHash', $row);
            $this->assertArrayNotHasKey('secretKey', $row);
        }

        $this->assertSame(['nameKeyword' => null, 'emailKeyword' => null], $final->filters);
    }

    public function testNameFilterMatchesByName01(): void
    {
        // 鈴木 matches bob (鈴木 太郎) and login-test (鈴木 次郎).
        $final = ($this->becoming)(new GetCustomerListInput(nameKeyword: '鈴木'));

        $this->assertInstanceOf(CustomerListFetched::class, $final);
        $this->assertSame(2, $final->count);
        $emails = array_column($final->customers, 'email');
        $this->assertContains('bob@example.com', $emails);
        $this->assertContains('login-test@example.com', $emails);
    }

    public function testNameFilterMatchesByCompanyName(): void
    {
        // 'Acme' lives on bob's companyName field only.
        $final = ($this->becoming)(new GetCustomerListInput(nameKeyword: 'Acme'));

        $this->assertInstanceOf(CustomerListFetched::class, $final);
        $this->assertSame(1, $final->count);
        $this->assertSame('bob@example.com', $final->customers[0]['email']);
    }

    public function testEmailFilterNarrowsResults(): void
    {
        $final = ($this->becoming)(new GetCustomerListInput(emailKeyword: 'alice'));

        $this->assertInstanceOf(CustomerListFetched::class, $final);
        $this->assertSame(1, $final->count);
        $this->assertSame('alice@example.com', $final->customers[0]['email']);
        $this->assertSame(['nameKeyword' => null, 'emailKeyword' => 'alice'], $final->filters);
    }

    public function testBothFiltersAndedTogether(): void
    {
        // 鈴木 matches bob + login-test by name, but only login-test
        // also matches the email substring 'login-test'.
        $final = ($this->becoming)(new GetCustomerListInput(
            nameKeyword: '鈴木',
            emailKeyword: 'login-test',
        ));

        $this->assertInstanceOf(CustomerListFetched::class, $final);
        $this->assertSame(1, $final->count);
        $this->assertSame('login-test@example.com', $final->customers[0]['email']);
    }

    public function testFiltersWithNoMatchReturnEmpty(): void
    {
        $final = ($this->becoming)(new GetCustomerListInput(nameKeyword: 'NoSuchPerson'));

        $this->assertInstanceOf(CustomerListFetched::class, $final);
        $this->assertSame(0, $final->count);
        $this->assertSame([], $final->customers);
    }

    public function testNoAdminSessionRaisesUnauthorizedAdminAccess(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new GetCustomerListInput());
    }
}
