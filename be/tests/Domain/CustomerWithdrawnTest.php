<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\CustomerWithdrawn;
use MyVendor\BeMart\Be\Input\AddCartItemInput;
use MyVendor\BeMart\Be\Input\WithdrawCustomerInput;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeMailer;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function count;
use function dirname;

final class CustomerWithdrawnTest extends TestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped('Stateful write/readback scenario is covered by the SQL suite.');
    }

    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const ALICE_EMAIL = 'alice@example.com';
    private const ALICE_DUMMY_EMAIL = 'withdrawn-0123456789abcdef0123456789abcdef@example.invalid';

    private BecomingInterface $becoming;
    private CustomerQueryInterface $customerStorage;
    private FakeMailer $mailer;

    private function buildInjector(string|null $sessionCustomerId): Injector
    {
        $session = new FakeSession($sessionCustomerId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(CustomerSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        return new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
    }

    private function rebindSession(string|null $sessionCustomerId): void
    {
        $injector = $this->buildInjector($sessionCustomerId);
        $this->becoming = $injector->getInstance(BecomingInterface::class);
        $this->customerStorage = $injector->getInstance(CustomerQueryInterface::class);
        $this->mailer = $injector->getInstance(FakeMailer::class);
    }

    public function testHappyPathReplacesEmailAndClearsCart(): void
    {
        $this->rebindSession(self::ALICE_ID);

        $final = ($this->becoming)(new WithdrawCustomerInput());

        $this->assertInstanceOf(CustomerWithdrawn::class, $final);
        $this->assertSame(self::ALICE_ID, $final->customerId);
        $this->assertSame(self::ALICE_EMAIL, $final->originalEmail);
        $this->assertSame(self::ALICE_DUMMY_EMAIL, $final->dummyEmail);
        $this->assertTrue($final->cleared);

        // Original email slot is freed; dummy email now holds the row.
        $this->assertNull($this->customerStorage->byEmail(self::ALICE_EMAIL));
        $persisted = $this->customerStorage->item(self::ALICE_ID);
        assert($persisted !== null);
        $this->assertSame(self::ALICE_DUMMY_EMAIL, $persisted->email);
        $this->assertSame(CustomerWithdrawn::STATUS_WITHDRAWN, $persisted->customerStatus);
        $this->assertSame(3, $persisted->customerStatus);
    }

    public function testCartIsCleared(): void
    {
        $this->rebindSession(self::ALICE_ID);

        // Pre-seed alice's session-prefix-1 carts via a normal add.
        ($this->becoming)(new AddCartItemInput('sample-001', 1));
        $this->assertNotEmpty(
            $this->cartStorage->getBySessionPrefix('session-prefix-1'),
            'Pre-condition: alice must hold at least one cart under session-prefix-1.',
        );

        ($this->becoming)(new WithdrawCustomerInput());

        $this->assertSame(
            [],
            $this->cartStorage->getBySessionPrefix('session-prefix-1'),
            'Post-condition: session-prefix-1 carts must be wiped.',
        );
    }

    public function testMailSentToOriginalEmail(): void
    {
        $this->rebindSession(self::ALICE_ID);

        $before = count($this->mailer->withdrawConfirmations);
        ($this->becoming)(new WithdrawCustomerInput());

        $after = $this->mailer->withdrawConfirmations;
        $this->assertCount($before + 1, $after);
        $last = $after[count($after) - 1];
        $this->assertSame(self::ALICE_EMAIL, $last['email']);
        $this->assertSame('山田', $last['name01']);
        $this->assertSame('アリス', $last['name02']);
    }

    public function testIdempotentReWithdrawIsNoOp(): void
    {
        $this->rebindSession(self::ALICE_ID);

        ($this->becoming)(new WithdrawCustomerInput());
        $mailsAfterFirst = count($this->mailer->withdrawConfirmations);

        // Replay — the customer record is already STATUS_WITHDRAWN, so
        // the Final must short-circuit: no second mail, no second
        // cart-clear, no re-replace.
        $final = ($this->becoming)(new WithdrawCustomerInput());

        $this->assertInstanceOf(CustomerWithdrawn::class, $final);
        $this->assertSame(self::ALICE_DUMMY_EMAIL, $final->dummyEmail);
        $this->assertSame(self::ALICE_DUMMY_EMAIL, $final->originalEmail);
        $this->assertTrue($final->cleared);
        $this->assertCount(
            $mailsAfterFirst,
            $this->mailer->withdrawConfirmations,
            'Idempotent replay must NOT send a second withdrawal mail.',
        );
    }

    public function testNoSessionRaisesUnauthenticated(): void
    {
        $this->rebindSession(null);

        $this->expectException(UnauthenticatedException::class);
        ($this->becoming)(new WithdrawCustomerInput());
    }

    public function testSessionPointsToMissingCustomerRaisesUnauthenticated(): void
    {
        $this->rebindSession('nonexistent-customer-id-zzzz9999');

        $this->expectException(UnauthenticatedException::class);
        ($this->becoming)(new WithdrawCustomerInput());
    }
}
