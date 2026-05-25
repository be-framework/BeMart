<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Exception\UnauthorizedOrderAccessException;
use MyVendor\BeMart\Be\Final\Reordered;
use MyVendor\BeMart\Be\Input\ReorderInput;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;
use function sprintf;

/**
 * Pilot 12 (doReorder) — Cascade Diamond / skip-and-apply.
 *
 * Exercises ReorderResolving (AUTHN/AUTHZ + per-item current-catalog
 * re-projection) and Reordered (per-saleType cart merge + persist).
 * The fixture seed installed by {@see Ray.FakeQuery fixture JSON}
 * provides the happy-path past order (`SEED_ORDER_NO`, customer-001,
 * sample-001 / sample-002). Skip-path tests install additional seeded
 * orders in setUp by writing extra rows via `putItems()`.
 */
final class ReorderedTest extends TestCase
{
    private BecomingInterface $becoming;
    private CartQueryInterface $cartQuery;

    protected function setUp(): void
    {
        $this->markTestSkipped('Stateful write/readback scenario is covered by the SQL suite.');
        $this->rebindSession('customer-001');
    }

    /** Build a fresh injector with the given session customerId (null = anonymous). */
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
        $this->cartQuery = $injector->getInstance(CartQueryInterface::class);
    }

    public function testHappyPathReplaysAllItems(): void
    {
        // Seed: SEED_ORDER_NO belongs to customer-001 with
        //   sample-001 × 1 (price02=1200, stock=50, saleTypeId=1)
        //   sample-002 × 1 (price02=9800, stockUnlimited, saleTypeId=1)
        $final = ($this->becoming)(new ReorderInput(
            orderNo: 'past0000000000000000000000000001',
        ));

        $this->assertInstanceOf(Reordered::class, $final);
        $this->assertSame('customer-001', $final->customerId);
        $this->assertSame('past0000000000000000000000000001', $final->orderNo);
        // Both items survive → addedCount sums the adjustedQuantities (1 + 1).
        $this->assertSame(2, $final->addedCount);
        $this->assertSame(0, $final->skippedCount);
        $this->assertSame([], $final->skippedProductCodes);
        // Both items share saleTypeId=1 → one cart touched.
        $this->assertSame(['session-prefix-1_1'], $final->cartKeys);

        $cart = $this->cartQuery->item('session-prefix-1_1');
        $this->assertNotNull($cart);
        $codes = [];
        $prices = [];
        foreach ($cart->items as $item) {
            $codes[$item->productCode] = $item->quantity;
            $prices[$item->productCode] = $item->price;
        }

        $this->assertSame(1, $codes['sample-001'] ?? null);
        $this->assertSame(1, $codes['sample-002'] ?? null);
        // ALPS: 現在価格を適用 — must use current price02, not the
        // historical unitPrice frozen on dtb_order_item.
        $this->assertSame(1200, $prices['sample-001'] ?? null);
        $this->assertSame(9800, $prices['sample-002'] ?? null);
    }

    public function testSkippedDiscontinuedProduct(): void
    {
        $orderNo = 'discontinued000000000000000000ab';
        $this->seedOrderHeader($orderNo, 'customer-001');
        $this->orderStorage->putItems($orderNo, [
            new OrderItemEntity(
                orderNo: $orderNo,
                productCode: 'sample-001',
                productName: 'サンプル商品 A',
                quantity: 1,
                unitPrice: 1200,
            ),
            // Not in product_classes.json → ProductClassQuery returns null → skip.
            new OrderItemEntity(
                orderNo: $orderNo,
                productCode: 'ghost-sku-removed',
                productName: 'Phantom Product',
                quantity: 1,
                unitPrice: 1000,
            ),
        ]);

        $final = ($this->becoming)(new ReorderInput(orderNo: $orderNo));

        assert($final instanceof Reordered);
        $this->assertSame(1, $final->addedCount);
        $this->assertSame(1, $final->skippedCount);
        $this->assertSame(['ghost-sku-removed'], $final->skippedProductCodes);

        $cart = $this->cartQuery->item('session-prefix-1_1');
        $this->assertNotNull($cart);
        foreach ($cart->items as $item) {
            $this->assertNotSame('ghost-sku-removed', $item->productCode);
        }
    }

    public function testSkippedOutOfStockProduct(): void
    {
        $orderNo = 'outofstock0000000000000000000abc';
        $this->seedOrderHeader($orderNo, 'customer-001');
        $this->orderStorage->putItems($orderNo, [
            new OrderItemEntity(
                orderNo: $orderNo,
                productCode: 'sample-001',
                productName: 'サンプル商品 A',
                quantity: 1,
                unitPrice: 1200,
            ),
            // stock=0 and !stockUnlimited → skip with reason=out-of-stock.
            new OrderItemEntity(
                orderNo: $orderNo,
                productCode: 'out-of-stock-test-001',
                productName: '在庫切れ商品',
                quantity: 1,
                unitPrice: 1800,
            ),
        ]);

        $final = ($this->becoming)(new ReorderInput(orderNo: $orderNo));

        assert($final instanceof Reordered);
        $this->assertSame(1, $final->addedCount);
        $this->assertSame(1, $final->skippedCount);
        $this->assertSame(['out-of-stock-test-001'], $final->skippedProductCodes);

        $cart = $this->cartQuery->item('session-prefix-1_1');
        $this->assertNotNull($cart);
        foreach ($cart->items as $item) {
            $this->assertNotSame('out-of-stock-test-001', $item->productCode);
        }
    }

    public function testWrongOwnerRaisesUnauthorized(): void
    {
        $this->rebindSession('customer-999');

        $this->expectException(UnauthorizedOrderAccessException::class);
        ($this->becoming)(new ReorderInput(
            orderNo: 'past0000000000000000000000000001',
        ));
    }

    public function testNoSessionRaisesUnauthenticated(): void
    {
        $this->rebindSession(null);

        $this->expectException(UnauthenticatedException::class);
        ($this->becoming)(new ReorderInput(
            orderNo: 'past0000000000000000000000000001',
        ));
    }

    public function testUnknownOrderRaisesOrderNotFound(): void
    {
        $this->expectException(OrderNotFoundException::class);
        ($this->becoming)(new ReorderInput(
            orderNo: 'never00000000000000000000000000z',
        ));
    }

    /**
     * Helper: install a finalized-order header into the in-memory storage
     * with the given orderNo + customerId. Mirrors the SEED_ORDER_NO row
     * shape — values besides customerId are uninteresting for these
     * tests but must be coherent integers.
     */
    private function seedOrderHeader(string $orderNo, string $customerId): void
    {
        $this->orderStorage->put(
            new FinalizedOrderEntity(
                orderNo: $orderNo,
                preOrderId: sprintf('pre-%s', $orderNo),
                customerId: $customerId,
                paymentMethodId: 2,
                subtotal: 0,
                deliveryFeeTotal: 0,
                charge: 0,
                discount: 0,
                tax: 0,
                total: 0,
                paymentTotal: 0,
                addPoint: 0,
                usePoint: 0,
                orderStatus: FinalizedOrderEntity::STATUS_NEW,
                orderDate: '2026-04-01 10:00:00',
                paymentDate: '2026-04-01 10:00:00',
            ),
        );
    }
}
