<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeFinalizedOrderStorage;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function array_map;
use function dirname;

/**
 * Pilot 12 (doReorder) infrastructure: read path for past order items.
 *
 * Validates `OrderQueryInterface::itemsByOrderNo` against the seed fixture
 * installed by FakeFinalizedOrderStorage. Pilot 12 itself is not yet
 * implemented; this test only locks the contract Pilot 12 will consume.
 */
final class OrderItemQueryTest extends TestCase
{
    private OrderQueryInterface $orderQuery;
    private FakeFinalizedOrderStorage $orderStorage;

    protected function setUp(): void
    {
        $module = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->orderQuery = $injector->getInstance(OrderQueryInterface::class);
        $this->orderStorage = $injector->getInstance(FakeFinalizedOrderStorage::class);
    }

    public function testSeededPastOrderExposesItems(): void
    {
        $items = $this->orderQuery->itemsByOrderNo(FakeFinalizedOrderStorage::SEED_ORDER_NO);

        $this->assertNotEmpty($items, 'Seed fixture must install at least one item for the past order.');
        foreach ($items as $item) {
            $this->assertInstanceOf(OrderItemEntity::class, $item);
            $this->assertSame(FakeFinalizedOrderStorage::SEED_ORDER_NO, $item->orderNo);
        }

        $codes = array_map(static fn (OrderItemEntity $i): string => $i->productCode, $items);
        $this->assertContains('sample-001', $codes);
        $this->assertContains('sample-002', $codes);
    }

    public function testUnknownOrderReturnsEmptyList(): void
    {
        $items = $this->orderQuery->itemsByOrderNo('never0000000000000000000000000000');

        $this->assertSame([], $items);
    }

    public function testPutItemsRoundTripsThroughQuery(): void
    {
        $orderNo = 'roundtrip0000000000000000000000a';
        $expected = [
            new OrderItemEntity(
                orderNo: $orderNo,
                productCode: 'sample-001',
                productName: 'サンプル商品 A',
                quantity: 2,
                unitPrice: 1200,
            ),
        ];
        $this->orderStorage->putItems($orderNo, $expected);

        $this->assertSame($expected, $this->orderQuery->itemsByOrderNo($orderNo));
    }
}
