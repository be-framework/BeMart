<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Auth\CartSessionPrefixInterface;
use MyVendor\BeMart\Auth\CustomerSessionWriterInterface;
use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\Result\SavedCart;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Module\TestModule;
use MyVendor\BeMart\Tests\Support\RecordingCustomerSessionWriter;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class WithdrawResourceTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';

    private ResourceInterface $resource;
    private RecordingCustomerSessionWriter $sessionWriter;

    protected function setUp(): void
    {
        $this->rebindSession(self::ALICE_ID);
    }

    private function rebindSession(string|null $customerId): void
    {
        $session = new FakeSession($customerId);
        $this->sessionWriter = new RecordingCustomerSessionWriter();
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session, $this->sessionWriter) extends AbstractModule {
            public function __construct(
                private readonly FakeSession $session,
                private readonly RecordingCustomerSessionWriter $sessionWriter,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(CustomerSession::class)->toInstance($this->session);
                $this->bind(CustomerSessionWriterInterface::class)->toInstance($this->sessionWriter);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsFormMetadata(): void
    {
        $ro = $this->resource->get('page://self/mypage/withdraw');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('goMypageWithdraw', $ro->body['transitionId']);
        $this->assertSame(['csrfToken'], $ro->body['fields']);
        $this->assertSame('POST', $ro->body['submitTo']['method']);
        $this->assertSame('page://self/mypage/withdraw', $ro->body['submitTo']['href']);
        $this->assertNull($ro->body['csrfToken']);
    }

    public function testOnGetShowsCurrentCustomer(): void
    {
        $ro = $this->resource->get('page://self/mypage/withdraw');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertSame('alice@example.com', $ro->body['email']);
        $this->assertSame('山田', $ro->body['name01']);
        $this->assertSame('アリス', $ro->body['name02']);
    }

    public function testOnGetUnauthenticatedReturns401(): void
    {
        $this->rebindSession(null);

        $ro = $this->resource->get('page://self/mypage/withdraw');

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
        $this->assertStringContainsString('ログイン', $ro->body['message']);
    }

    public function testOnPostHappyPathReturns200(): void
    {
        $ro = $this->resource->post('page://self/mypage/withdraw', [
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertArrayNotHasKey('dummyEmail', $ro->body);
        $this->assertTrue($ro->body['cleared']);
        $this->assertStringContainsString('退会', $ro->body['message']);
    }

    public function testOnPostClearsTheCustomerSession(): void
    {
        // The account the session names is gone; leaving the session
        // live would keep every /mypage transition authenticated.
        $ro = $this->resource->post('page://self/mypage/withdraw', [
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertTrue($this->sessionWriter->cleared);
    }

    public function testOnPostWithoutSessionReturns401(): void
    {
        $this->rebindSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthenticatedException::class);

        $this->resource->post('page://self/mypage/withdraw', [
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    /**
     * The cart-clear side-effect is scoped by sessionPrefix. Honouring a
     * body field would let the caller wipe somebody else's cart partition
     * while leaving their own carts behind.
     */
    public function testOnPostClearsOwnCartPartitionNotTheClientSuppliedOne(): void
    {
        $cartCommand = new class implements CartCommandInterface {
            /** @var list<string> */
            public array $clearedPrefixes = [];

            public function save(CartEntity $cart): SavedCart
            {
                return new SavedCart();
            }

            public function clearByPreOrderId(string $preOrderId): void
            {
            }

            public function clearBySessionPrefix(string $sessionPrefix): void
            {
                $this->clearedPrefixes[] = $sessionPrefix;
            }
        };
        $cartSessionPrefix = new class implements CartSessionPrefixInterface {
            public function prefix(): string
            {
                return 'alice-own-session';
            }
        };
        $session = new FakeSession(self::ALICE_ID);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class ($session, $cartSessionPrefix, $cartCommand) extends AbstractModule {
            public function __construct(
                private readonly FakeSession $session,
                private readonly CartSessionPrefixInterface $cartSessionPrefix,
                private readonly CartCommandInterface $cartCommand,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(CustomerSession::class)->toInstance($this->session);
                $this->bind(CartSessionPrefixInterface::class)->toInstance($this->cartSessionPrefix);
                $this->bind(CartCommandInterface::class)->toInstance($this->cartCommand);
            }
        });
        $resource = (new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test'))
            ->getInstance(ResourceInterface::class);

        $ro = $resource->post('page://self/mypage/withdraw', [
            'sessionPrefix' => 'victim-session',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(['alice-own-session'], $cartCommand->clearedPrefixes);
    }
}
