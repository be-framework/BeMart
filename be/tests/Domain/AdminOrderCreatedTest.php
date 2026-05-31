<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminOrderCreated;
use MyVendor\BeMart\Be\Input\AdminCreateOrderInput;
use MyVendor\BeMart\Be\Reason\Query\OrderItemCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\Param\OrderItemList;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Domain coverage for {@see AdminOrderCreated} (doCreateOrder) — the
 * PurchaseFlow recompute and the dtb_order_item snapshot wiring.
 *
 * The snapshot persistence itself is durable-only; here a capturing stub
 * stands in for {@see OrderItemCommandInterface} so the wiring (orderNo +
 * item vector) is observable without a database. The SQL path is pinned
 * by {@see \MyVendor\BeMart\Be\Tests\Sql\SqlOrderItemCommandTest}.
 */
final class AdminOrderCreatedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';

    /** A capturing stub recording what the Final asked to persist. */
    private object $orderItemCommand;

    private function becoming(string|null $adminId): BecomingInterface
    {
        $session = new FakeAdminSession($adminId);
        $this->orderItemCommand = new class implements OrderItemCommandInterface {
            public string|null $orderNo = null;
            public OrderItemList|null $items = null;

            public function register(string $orderNo, OrderItemList $items): void
            {
                $this->orderNo = $orderNo;
                $this->items = $items;
            }
        };

        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $command = $this->orderItemCommand;
        $override = new class ($session, $command) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly OrderItemCommandInterface $command,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
                $this->bind(OrderItemCommandInterface::class)->toInstance($this->command);
            }
        };
        $base->override($override);

        return (new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test'))
            ->getInstance(BecomingInterface::class);
    }

    /** @return list<array{productCode: string, productName: string, unitPrice: int, quantity: int}> */
    private function items(): array
    {
        return [
            ['productCode' => 'SKU-1', 'productName' => '商品A', 'unitPrice' => 500, 'quantity' => 2],
            ['productCode' => 'SKU-2', 'productName' => '商品B', 'unitPrice' => 1200, 'quantity' => 1],
        ];
    }

    public function testPurchaseFlowDerivesTotalsFromLineItems(): void
    {
        $final = ($this->becoming(self::TEST_ADMIN_ID))(new AdminCreateOrderInput(
            customerId: self::ALICE_ID,
            paymentMethodId: 2,
            orderItems: $this->items(),
            deliveryFeeTotal: 300,
        ));

        $this->assertInstanceOf(AdminOrderCreated::class, $final);
        // subtotal = 2×500 + 1×1200 = 2200; tax 10% = 220; +delivery 300 → 2720.
        $this->assertSame(2200, $final->subtotal);
        $this->assertSame(220, $final->tax);
        $this->assertSame(300, $final->deliveryFeeTotal);
        $this->assertSame(2720, $final->total);
        $this->assertSame(2720, $final->paymentTotal);
        // addPoint = 1% of base total (2720) = 27.
        $this->assertSame(27, $final->addPoint);
        $this->assertSame(2, $final->itemCount);
    }

    public function testChargeAndDiscountAdjustTheBaseTotal(): void
    {
        $final = ($this->becoming(self::TEST_ADMIN_ID))(new AdminCreateOrderInput(
            customerId: self::ALICE_ID,
            paymentMethodId: 2,
            orderItems: [
                ['productCode' => 'SKU-1', 'productName' => '商品A', 'unitPrice' => 1000, 'quantity' => 1],
            ],
            charge: 50,
            discount: 200,
        ));

        // base = 1000 + 100 tax = 1100; +50 charge -200 discount → 950.
        $this->assertSame(50, $final->charge);
        $this->assertSame(200, $final->discount);
        $this->assertSame(950, $final->total);
        $this->assertSame(950, $final->paymentTotal);
    }

    public function testSnapshotIsHandedToTheOrderItemCommand(): void
    {
        $final = ($this->becoming(self::TEST_ADMIN_ID))(new AdminCreateOrderInput(
            customerId: self::ALICE_ID,
            paymentMethodId: 2,
            orderItems: $this->items(),
        ));

        $this->assertInstanceOf(AdminOrderCreated::class, $final);
        $this->assertSame($final->orderNo, $this->orderItemCommand->orderNo);
        $this->assertNotNull($this->orderItemCommand->items);

        $captured = $this->orderItemCommand->items->items;
        $this->assertCount(2, $captured);
        $this->assertSame('SKU-1', $captured[0]->productCode);
        $this->assertSame('商品A', $captured[0]->productName);
        $this->assertSame(2, $captured[0]->quantity);
        $this->assertSame(500, $captured[0]->unitPrice);
        // The snapshot rows carry the server-allocated orderNo.
        $this->assertSame($final->orderNo, $captured[0]->orderNo);
    }

    public function testNoAdminSessionRaisesUnauthorizedAdmin(): void
    {
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming(null))(new AdminCreateOrderInput(
            customerId: self::ALICE_ID,
            paymentMethodId: 2,
            orderItems: $this->items(),
        ));
    }

    public function testEmptyItemVectorIsRejected(): void
    {
        $this->expectException(SemanticVariableException::class);
        ($this->becoming(self::TEST_ADMIN_ID))(new AdminCreateOrderInput(
            customerId: self::ALICE_ID,
            paymentMethodId: 2,
            orderItems: [],
        ));
    }
}
