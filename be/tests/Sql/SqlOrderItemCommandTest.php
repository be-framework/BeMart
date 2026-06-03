<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderItemCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderItemQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\Param\OrderItemList;

/**
 * Storage-layer coverage for {@see OrderItemCommandInterface} — the
 * dtb_order_item snapshot write (doCreateOrder / checkout).
 *
 * Pins the SQL path against the SAME column↔field projection
 * {@see OrderItemQueryInterface::listByOrderNo} reads back, so a
 * read-after-write round-trips exactly.
 *
 * Surprises this suite locks in:
 *  - `register` resolves the parent dtb_order row by `order_no`, then
 *    fans the item vector out via JSON_TABLE — the order row MUST be
 *    written first (the Final guarantees that ordering).
 *  - dtb_order_item's NOT NULL columns (product_name, price, quantity,
 *    tax, tax_rate, tax_adjust, discriminator_type) are all supplied by
 *    the INSERT; the FK-bearing columns (product_id, product_class_id,
 *    order_item_type_id, …) are written NULL so no master seeding is
 *    needed.
 *  - An empty vector writes nothing (JSON_TABLE over `[]` yields no
 *    rows) rather than erroring.
 */
final class SqlOrderItemCommandTest extends AbstractSqlTestCase
{
    public function testRegisterPersistsTheSnapshotInOrder(): void
    {
        $this->insertOrder(['order_no' => 'OI-ORD-001']);

        $command = $this->sql(OrderItemCommandInterface::class);
        $command->register('OI-ORD-001', OrderItemList::fromArray([
            new OrderItemEntity('OI-ORD-001', 'SKU-1', '商品A', 2, 500),
            new OrderItemEntity('OI-ORD-001', 'SKU-2', '商品B', 1, 1200),
        ]));

        $rows = $this->sql(OrderItemQueryInterface::class)->listByOrderNo('OI-ORD-001');

        $this->assertCount(2, $rows);
        $this->assertSame('SKU-1', $rows[0]->productCode);
        $this->assertSame('商品A', $rows[0]->productName);
        $this->assertSame(2, $rows[0]->quantity);
        $this->assertSame(500, $rows[0]->unitPrice);
        $this->assertSame('SKU-2', $rows[1]->productCode);
        $this->assertSame(1200, $rows[1]->unitPrice);
    }

    public function testRegisterResolvesParentByOrderNo(): void
    {
        $this->insertOrder(['order_no' => 'OI-A']);
        $this->insertOrder(['order_no' => 'OI-B']);

        $command = $this->sql(OrderItemCommandInterface::class);
        $command->register('OI-A', OrderItemList::fromArray([
            new OrderItemEntity('OI-A', 'A-1', 'A item', 1, 100),
        ]));

        $query = $this->sql(OrderItemQueryInterface::class);
        $this->assertCount(1, $query->listByOrderNo('OI-A'));
        // The other order's snapshot stays empty — the JOIN scopes the
        // INSERT to the matching order_no only.
        $this->assertSame([], $query->listByOrderNo('OI-B'));
    }

    public function testRegisterEmptyVectorWritesNothing(): void
    {
        $this->insertOrder(['order_no' => 'OI-EMPTY']);

        $command = $this->sql(OrderItemCommandInterface::class);
        $command->register('OI-EMPTY', OrderItemList::fromArray([]));

        $this->assertSame([], $this->sql(OrderItemQueryInterface::class)->listByOrderNo('OI-EMPTY'));
    }
}
