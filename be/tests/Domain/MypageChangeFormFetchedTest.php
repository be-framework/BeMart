<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\MypageChangeFormFetched;
use MyVendor\BeMart\Be\Input\GetMypageChangeInput;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Pilot (goMypageChange) — Direct safe-read of the customer's
 * current values to pre-populate the edit form.
 */
final class MypageChangeFormFetchedTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';

    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $this->rebindSession(self::ALICE_ID);
    }

    private function rebindSession(string|null $customerId): void
    {
        $session = new FakeSession($customerId);
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

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathReturnsAlicesCurrentValues(): void
    {
        $final = ($this->becoming)(new GetMypageChangeInput());

        $this->assertInstanceOf(MypageChangeFormFetched::class, $final);
        $this->assertSame(self::ALICE_ID, $final->customerId);
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

        $this->assertSame('POST', $final->submitTo['method']);
        $this->assertSame('page://self/mypage/change', $final->submitTo['href']);
    }

    public function testNoSessionRaisesUnauthenticated(): void
    {
        $this->rebindSession(null);

        $this->expectException(UnauthenticatedException::class);
        ($this->becoming)(new GetMypageChangeInput());
    }

    public function testSessionPointsToMissingCustomerRaisesUnauthenticated(): void
    {
        $this->rebindSession('nonexistent-customer-id-zzzz9999');

        $this->expectException(UnauthenticatedException::class);
        ($this->becoming)(new GetMypageChangeInput());
    }
}
