<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\CustomerNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminCustomerDeleted;
use MyVendor\BeMart\Be\Input\AdminDeleteCustomerInput;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeCustomerStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeMailer;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function count;
use function dirname;

final class AdminCustomerDeletedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const ALICE_EMAIL = 'alice@example.com';
    private const ALICE_DUMMY_EMAIL = 'withdrawn-0123456789abcdef0123456789abcdef@example.invalid';

    private BecomingInterface $becoming;
    private FakeCustomerStorage $customerStorage;
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
        $this->customerStorage = $injector->getInstance(FakeCustomerStorage::class);
        $this->mailer = $injector->getInstance(FakeMailer::class);
    }

    public function testHappyPathReplacesEmailAndFlipsStatus(): void
    {
        $final = ($this->becoming)(new AdminDeleteCustomerInput(
            customerId: self::ALICE_ID,
        ));

        $this->assertInstanceOf(AdminCustomerDeleted::class, $final);
        $this->assertSame(self::ALICE_ID, $final->customerId);
        $this->assertSame(self::ALICE_EMAIL, $final->originalEmail);
        $this->assertFalse($final->alreadyDeleted);

        // Original email slot freed; dummy email now holds the row.
        $this->assertNull($this->customerStorage->getByEmail(self::ALICE_EMAIL));
        $persisted = $this->customerStorage->getById(self::ALICE_ID);
        assert($persisted !== null);
        $this->assertSame(self::ALICE_DUMMY_EMAIL, $persisted->email);
        $this->assertSame(AdminCustomerDeleted::STATUS_WITHDRAWN, $persisted->customerStatus);
        $this->assertSame(3, $persisted->customerStatus);
    }

    public function testMailSentToOriginalEmail(): void
    {
        $before = count($this->mailer->withdrawConfirmations());
        ($this->becoming)(new AdminDeleteCustomerInput(
            customerId: self::ALICE_ID,
        ));

        $after = $this->mailer->withdrawConfirmations();
        $this->assertCount($before + 1, $after);
        $last = $after[count($after) - 1];
        $this->assertSame(self::ALICE_EMAIL, $last['email']);
        $this->assertSame('山田', $last['name01']);
        $this->assertSame('アリス', $last['name02']);
    }

    public function testIdempotentReDeleteIsNoOp(): void
    {
        ($this->becoming)(new AdminDeleteCustomerInput(
            customerId: self::ALICE_ID,
        ));
        $mailsAfterFirst = count($this->mailer->withdrawConfirmations());

        // Replay — the customer record is already STATUS_WITHDRAWN, so
        // the Final must short-circuit: alreadyDeleted=true, no second
        // mail, no second update.
        $final = ($this->becoming)(new AdminDeleteCustomerInput(
            customerId: self::ALICE_ID,
        ));

        $this->assertInstanceOf(AdminCustomerDeleted::class, $final);
        $this->assertSame(self::ALICE_ID, $final->customerId);
        $this->assertTrue($final->alreadyDeleted);
        // After the first delete the persisted `email` is the dummy,
        // and that is what `originalEmail` surfaces on replay.
        $this->assertSame(self::ALICE_DUMMY_EMAIL, $final->originalEmail);
        $this->assertCount(
            $mailsAfterFirst,
            $this->mailer->withdrawConfirmations(),
            'Idempotent replay must NOT send a second withdrawal mail.',
        );
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
