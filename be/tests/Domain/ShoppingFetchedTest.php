<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\ShoppingFetched;
use MyVendor\BeMart\Be\Input\GetShoppingInput;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class ShoppingFetchedTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';

    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        // Default to alice (a real Customer fixture row). The AppModule
        // default binds to 'customer-001' which has no Customer entry —
        // suitable for Pilot 5's preOrder-AUTHZ scenarios but not for
        // goShopping which actually loads the customer profile.
        $this->rebindBecoming(self::ALICE_ID);
    }

    private function rebindBecoming(string|null $customerId): void
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

    public function testHappyPathReturnsCustomerCartsAndPaymentMethods(): void
    {
        $final = ($this->becoming)(new GetShoppingInput(sessionPrefix: 'session-prefix-1'));

        $this->assertInstanceOf(ShoppingFetched::class, $final);
        $this->assertSame(self::ALICE_ID, $final->customerId);
        $this->assertSame('alice@example.com', $final->email);
        $this->assertSame('山田', $final->name01);
        $this->assertSame('アリス', $final->name02);

        // Default shipping pulled from alice's customer fixture.
        $this->assertSame('1500001', $final->defaultShippingAddress['postalCode']);
        $this->assertSame(13, $final->defaultShippingAddress['pref']);
        $this->assertSame('渋谷区', $final->defaultShippingAddress['addr01']);
        $this->assertSame('神宮前1-1-1', $final->defaultShippingAddress['addr02']);
        $this->assertSame('0312345678', $final->defaultShippingAddress['phoneNumber']);

        // Static FakeQuery fixture has session-prefix-1_1 with sample-001 × 3
        // plus an empty preorder cart.
        $this->assertSame(2, $final->cartCount);
        $this->assertSame(3600, $final->totalPrice);
        $this->assertSame(0, $final->deliveryFeeTotal);
        $this->assertCount(2, $final->carts);
        $this->assertSame('session-prefix-1_1', $final->carts[0]['cartKey']);
        $this->assertSame('通常販売', $final->carts[0]['saleTypeName']);

        // Two user-selectable methods (代金引換 + クレジットカード). The
        // fault-injection method (id=9) is intentionally absent.
        $this->assertCount(2, $final->paymentMethods);
        $this->assertSame(1, $final->paymentMethods[0]['paymentMethodId']);
        $this->assertSame('代金引換', $final->paymentMethods[0]['paymentMethodName']);
        $this->assertSame(2, $final->paymentMethods[1]['paymentMethodId']);
        $this->assertSame('クレジットカード', $final->paymentMethods[1]['paymentMethodName']);

        // With carts present (even if empty of items) canCheckout is true.
        $this->assertTrue($final->canCheckout);
    }

    public function testCanCheckoutFalseWhenSessionHasNoCarts(): void
    {
        $final = ($this->becoming)(new GetShoppingInput(sessionPrefix: 'no-such-session'));

        $this->assertInstanceOf(ShoppingFetched::class, $final);
        $this->assertSame(0, $final->cartCount);
        $this->assertSame([], $final->carts);
        $this->assertSame(0, $final->totalPrice);
        $this->assertFalse($final->canCheckout);

        // Payment methods are still enumerated — they are session-independent.
        $this->assertCount(2, $final->paymentMethods);
    }

    public function testAnonymousSessionRaisesUnauthenticated(): void
    {
        $this->rebindBecoming(null);

        $this->expectException(UnauthenticatedException::class);
        ($this->becoming)(new GetShoppingInput(sessionPrefix: 'session-prefix-1'));
    }

    public function testUnknownCustomerSessionRaisesUnauthenticated(): void
    {
        // Session points to a customerId that has no Customer fixture row.
        // The Final treats this the same as not-logged-in (Pilot 8 lesson —
        // no existence-signal leakage across the AAA boundary).
        $this->rebindBecoming('ghost-customer-no-such-row');

        $this->expectException(UnauthenticatedException::class);
        ($this->becoming)(new GetShoppingInput(sessionPrefix: 'session-prefix-1'));
    }
}
