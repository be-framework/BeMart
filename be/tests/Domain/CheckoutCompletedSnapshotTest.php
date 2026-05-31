<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Final\CheckoutCompleted;
use MyVendor\BeMart\Be\Input\CheckoutInput;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Query\OrderItemCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\Param\OrderItemList;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Domain coverage for {@see CheckoutCompleted}'s order-item snapshot
 * wiring — the storefront half of the project-wide guarantee that every
 * finalized order freezes its line items into dtb_order_item.
 *
 * The `aceface…` pre-order fixture (var/fake/query/order_by_pre_order_id.jsonl)
 * is owned by customer `0123…` and carries two lines (sample-001 ×2 and
 * preorder-2026-spring-bag ×1). A capturing stub stands in for
 * {@see OrderItemCommandInterface} so the snapshot vector is observable
 * without a database; the durable SQL path is pinned by
 * {@see \MyVendor\BeMart\Be\Tests\Sql\SqlOrderItemCommandTest} and the
 * end-to-end storefront write by CheckoutResourceSqlTest.
 */
final class CheckoutCompletedSnapshotTest extends TestCase
{
    private const PRE_ORDER_ID = 'aceface0000000000000000000000000000a11ce';
    private const OWNER_ID = '0123456789abcdef0123456789abcdef';

    private object $orderItemCommand;

    private function becoming(string $customerId): BecomingInterface
    {
        $this->orderItemCommand = new class implements OrderItemCommandInterface {
            public string|null $orderNo = null;
            public OrderItemList|null $items = null;

            public function register(string $orderNo, OrderItemList $items): void
            {
                $this->orderNo = $orderNo;
                $this->items = $items;
            }
        };

        $session = new FakeSession($customerId);
        $command = $this->orderItemCommand;
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session, $command) extends AbstractModule {
            public function __construct(
                private readonly FakeSession $session,
                private readonly OrderItemCommandInterface $command,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(CustomerSession::class)->toInstance($this->session);
                $this->bind(OrderItemCommandInterface::class)->toInstance($this->command);
            }
        };
        $base->override($override);

        return (new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test'))
            ->getInstance(BecomingInterface::class);
    }

    public function testCheckoutFreezesTheCartLinesIntoTheOrderItemSnapshot(): void
    {
        $final = ($this->becoming(self::OWNER_ID))(new CheckoutInput(self::PRE_ORDER_ID));

        $this->assertInstanceOf(CheckoutCompleted::class, $final);

        // The snapshot was handed to the command under the server-allocated
        // orderNo — the parent the SQL INSERT resolves against.
        $this->assertSame($final->orderNo, $this->orderItemCommand->orderNo);
        $this->assertNotNull($this->orderItemCommand->items);

        $captured = $this->orderItemCommand->items->items;
        $this->assertCount(2, $captured);

        $this->assertSame('sample-001', $captured[0]->productCode);
        $this->assertSame(2, $captured[0]->quantity);
        $this->assertSame(1200, $captured[0]->unitPrice);
        // The display name is frozen from the product-class read, not left
        // blank — proving the snapshot captures the receipt name.
        $this->assertNotSame('', $captured[0]->productName);
        $this->assertSame($final->orderNo, $captured[0]->orderNo);

        $this->assertSame('preorder-2026-spring-bag', $captured[1]->productCode);
        $this->assertSame(1, $captured[1]->quantity);
        $this->assertSame(13500, $captured[1]->unitPrice);
    }
}
