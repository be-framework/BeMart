<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class ShoppingResourceTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        // Default to alice — she has a customers.json fixture row so the
        // Final's `findById` resolves. The AppModule default binds the
        // session to 'customer-001' which has no matching customer row
        // and would trip UnauthenticatedException.
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
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsCheckoutReviewProjection(): void
    {
        $ro = $this->resource->get('page://self/shopping');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertSame('alice@example.com', $ro->body['email']);

        // Default shipping pulled from the customer record.
        $this->assertSame('1500001', $ro->body['defaultShippingAddress']['postalCode']);
        $this->assertSame('渋谷区', $ro->body['defaultShippingAddress']['addr01']);

        // session-prefix-1 carts exist in the fixture; canCheckout is true.
        $this->assertSame(2, $ro->body['cartCount']);
        $this->assertCount(2, $ro->body['carts']);
        $this->assertTrue($ro->body['canCheckout']);

        // User-selectable payment methods are surfaced.
        $this->assertCount(2, $ro->body['paymentMethods']);
        $this->assertSame('代金引換', $ro->body['paymentMethods'][0]['paymentMethodName']);
    }

    public function testOnGetWithFixtureCartReflectsTotal(): void
    {
        // Fake context is static-fixture based; add-then-read is covered
        // by the SQL suite. The session-prefix-1 fixture has sample-001 x3.
        $ro = $this->resource->get('page://self/shopping');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(3600, $ro->body['totalPrice']);
        $this->assertTrue($ro->body['canCheckout']);
    }

    public function testOnGetEmptySessionReturnsCanCheckoutFalse(): void
    {
        $ro = $this->resource->get('page://self/shopping', [
            'sessionPrefix' => 'no-such-session',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(0, $ro->body['cartCount']);
        $this->assertSame([], $ro->body['carts']);
        $this->assertFalse($ro->body['canCheckout']);
        // Payment methods are still listed even with no carts.
        $this->assertCount(2, $ro->body['paymentMethods']);
    }

    public function testOnGetUnauthenticatedReturns401(): void
    {
        $this->rebindSession(null);

        $ro = $this->resource->get('page://self/shopping');

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
        $this->assertSame('この操作を行うにはログインが必要です。', $ro->body['message']);
    }

    public function testOnGetUnknownCustomerSessionReturns401(): void
    {
        // Session points to a non-existent customerId — same 401 as anonymous.
        $this->rebindSession('ghost-customer-no-such-row');

        $ro = $this->resource->get('page://self/shopping');

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
        $this->assertSame('この操作を行うにはログインが必要です。', $ro->body['message']);
    }
}
