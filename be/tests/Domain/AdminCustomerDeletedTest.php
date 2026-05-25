<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\CustomerNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminCustomerDeleted;
use MyVendor\BeMart\Be\Input\AdminDeleteCustomerInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeMailer;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function count;
use function dirname;

final class AdminCustomerDeletedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const ALICE_EMAIL = 'alice@example.com';

    private BecomingInterface $becoming;
    private FakeMailer $mailer;

    protected function setUp(): void
    {
        $this->build(self::TEST_ADMIN_ID);
    }

    /**
     * Build a fresh injector with the given admin session adminId
     * (null = admin-anonymous). Same override pattern as
     * AdminCustomerCreatedTest / AdminCustomerFetchedTest.
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
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
        $this->mailer = $injector->getInstance(FakeMailer::class);
    }

    public function testHappyPathReturnsDeletedState(): void
    {
        $final = ($this->becoming)(new AdminDeleteCustomerInput(
            customerId: self::ALICE_ID,
        ));

        $this->assertInstanceOf(AdminCustomerDeleted::class, $final);
        $this->assertSame(self::ALICE_ID, $final->customerId);
        $this->assertSame(self::ALICE_EMAIL, $final->originalEmail);
        $this->assertFalse($final->alreadyDeleted);
        // FakeQuery fixtures are static; withdrawn persistence is covered by the SQL suite.
    }

    public function testMailSentToOriginalEmail(): void
    {
        $before = count($this->mailer->withdrawConfirmations);
        ($this->becoming)(new AdminDeleteCustomerInput(
            customerId: self::ALICE_ID,
        ));

        $after = $this->mailer->withdrawConfirmations;
        $this->assertCount($before + 1, $after);
        $last = $after[count($after) - 1];
        $this->assertSame(self::ALICE_EMAIL, $last['email']);
        $this->assertSame('山田', $last['name01']);
        $this->assertSame('アリス', $last['name02']);
    }

    public function testIdempotentReDeleteIsNoOp(): void
    {
        $this->markTestSkipped('Idempotent re-delete needs mutable persistence; covered by the SQL suite.');
    }

    public function testUnknownCustomerIdRaisesNotFound(): void
    {
        $this->expectException(CustomerNotFoundException::class);
        ($this->becoming)(new AdminDeleteCustomerInput(
            customerId: 'nonexistent-customer-id-zzzz9999',
        ));
    }

    public function testNoAdminSessionRaisesUnauthorizedAdmin(): void
    {
        $this->build(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new AdminDeleteCustomerInput(
            customerId: self::ALICE_ID,
        ));
    }

    public function testNoAdminSessionRefusesBeforeExistenceCheck(): void
    {
        // Anti-enumeration: admin-anonymous + unknown id ⇒ 403 not 404.
        // The AUTHZ guard must run BEFORE the existence probe so a
        // non-admin learns nothing about which customerIds resolve.
        $this->build(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new AdminDeleteCustomerInput(
            customerId: 'nonexistent-customer-id-zzzz9999',
        ));
    }
}
