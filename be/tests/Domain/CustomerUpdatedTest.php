<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\CustomerUpdated;
use MyVendor\BeMart\Be\Input\UpdateCustomerInput;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class CustomerUpdatedTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';

    private BecomingInterface $becoming;

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
                $this->bind(SessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        return new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
    }

    private function bindAs(string|null $sessionCustomerId): void
    {
        $injector = $this->buildInjector($sessionCustomerId);
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathPatchesOnlySpecifiedFields(): void
    {
        $this->bindAs(self::ALICE_ID);

        $final = ($this->becoming)(new UpdateCustomerInput(
            email: 'alice@example.com',
            phoneNumber: '0309998888',
        ));

        $this->assertInstanceOf(CustomerUpdated::class, $final);
        $this->assertSame('alice@example.com', $final->email);
        $this->assertSame('山田', $final->name01);
        $this->assertSame('アリス', $final->name02);
        // FakeQuery fixtures are static; patch persistence is covered by the SQL suite.
    }

    public function testEmailChangeReindexesAndChecksUniqueness(): void
    {
        $this->markTestSkipped('Email reindexing needs mutable persistence; covered by the SQL suite.');
    }

    public function testEmailChangeToTakenEmailRaises(): void
    {
        $this->bindAs(self::ALICE_ID);

        $this->expectException(EmailAlreadyRegisteredException::class);
        ($this->becoming)(new UpdateCustomerInput(
            email: 'bob@example.com',
        ));
    }

    public function testNoSessionRaisesUnauthenticated(): void
    {
        $this->bindAs(null);

        $this->expectException(UnauthenticatedException::class);
        ($this->becoming)(new UpdateCustomerInput(
            email: 'alice@example.com',
        ));
    }

    public function testSessionPointsToMissingCustomerRaisesUnauthenticated(): void
    {
        $this->bindAs('nonexistent-customer-id-zzzz9999');

        $this->expectException(UnauthenticatedException::class);
        ($this->becoming)(new UpdateCustomerInput(
            email: 'alice@example.com',
        ));
    }
}
