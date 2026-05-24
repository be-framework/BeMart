<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException;
use MyVendor\BeMart\Be\Exception\EmailFormatException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminCustomerCreated;
use MyVendor\BeMart\Be\Input\AdminCreateCustomerInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class AdminCustomerCreatedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $this->build(self::TEST_ADMIN_ID);
    }

    /**
     * Build a fresh injector with the given admin session adminId
     * (null = admin-anonymous). Same override pattern as
     * AdminLoggedOutTest.
     */
    private function build(string|null $adminId): void
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

    public function testHappyPathCreatesActiveCustomer(): void
    {
        $final = ($this->becoming)(new AdminCreateCustomerInput(
            email: 'admin-created@example.com',
            password: 'admin-set-passphrase-2026',
            name01: '管理',
            name02: '太郎',
        ));

        $this->assertInstanceOf(AdminCustomerCreated::class, $final);
        $this->assertSame('admin-created@example.com', $final->email);
        $this->assertSame('管理', $final->name01);
        $this->assertSame('太郎', $final->name02);
        $this->assertSame(100, $final->initialPoint);
        // ALPS doc: 仮会員フラグなしで即時本会員として登録 — status=2 (Active).
        $this->assertSame(2, $final->customerStatus);
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{32}\z/', $final->customerId);
        // FakeQuery fixtures are static; persistence readback is covered by the SQL suite.
    }

    public function testDuplicateEmailIsRejected(): void
    {
        // alice@example.com is in the seed fixture (shared with Pilot 4).
        $this->expectException(EmailAlreadyRegisteredException::class);
        ($this->becoming)(new AdminCreateCustomerInput(
            email: 'alice@example.com',
            password: 'admin-overwrite-attempt-2026',
            name01: '別人',
            name02: 'A',
        ));
    }

    public function testInvalidEmailRejected(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new AdminCreateCustomerInput(
                email: 'not-an-email',
                password: 'whatever-2026',
                name01: '佐藤',
                name02: '五郎',
            ));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(
                EmailFormatException::class,
                $e->getErrors()->exceptions[0],
            );

            throw $e;
        }
    }

    public function testAnonymousAdminIsRejected(): void
    {
        // Rebind to admin-anonymous and expect the AUTHZ guard at the
        // first Being to raise. This is the firewall-crossing case
        // distinguished from UnauthenticatedException (which is for the
        // customer firewall).
        $this->build(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new AdminCreateCustomerInput(
            email: 'no-admin@example.com',
            password: 'no-admin-passphrase-2026',
            name01: '無権限',
            name02: '次郎',
        ));
    }
}
