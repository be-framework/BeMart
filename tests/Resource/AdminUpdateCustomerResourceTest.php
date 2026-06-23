<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Real-submit proof for the admin customer-edit write handler
 * (doUpdateCustomerProfile). Mirrors AdminCreateCustomerResourceTest's
 * rebindAdminSession harness.
 *
 * The persisted-patch round-trip is delegated to the SQL suite — the
 * Fake query fixtures are static, so we assert on the returned Final
 * (and the failure exceptions), not on a re-read.
 */
final class AdminUpdateCustomerResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** Alice — seeded in be/var/fake/customers.json. */
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const ALICE_EMAIL = 'alice@example.com';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

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

    public function testOnPostUpdatesExistingCustomerAndReturns200(): void
    {
        $ro = $this->resource->post('page://self/admin/customer', [
            'customerId' => self::ALICE_ID,
            'email' => self::ALICE_EMAIL,
            'name01' => '更新',
            'name02' => '花子',
            'phoneNumber' => '0312345678',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('更新', $ro->body['name01']);
        $this->assertSame('花子', $ro->body['name02']);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertArrayHasKey('Location', $ro->headers);
        $this->assertStringContainsString(
            'customerId=' . self::ALICE_ID,
            $ro->headers['Location'],
        );
    }

    public function testOnPostAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->post('page://self/admin/customer', [
            'customerId' => self::ALICE_ID,
            'email' => self::ALICE_EMAIL,
            'name01' => '無権限',
            'name02' => '太郎',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostUnknownCustomerReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\CustomerNotFoundException::class);

        $this->resource->post('page://self/admin/customer', [
            'customerId' => 'ffffffffffffffffffffffffffffffff',
            'email' => 'ghost@example.com',
            'name01' => '幽霊',
            'name02' => '太郎',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostEmailChangeToTakenEmailReturns409(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException::class);

        // Alice tries to take Bob's already-registered email.
        $this->resource->post('page://self/admin/customer', [
            'customerId' => self::ALICE_ID,
            'email' => 'bob@example.com',
            'name01' => '更新',
            'name02' => '花子',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostInvalidEmailReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/admin/customer', [
            'customerId' => self::ALICE_ID,
            'email' => 'not-an-email',
            'name01' => '更新',
            'name02' => '花子',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }
}
