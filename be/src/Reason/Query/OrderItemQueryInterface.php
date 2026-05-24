<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Query\Factory\OrderItemFactory;
use Ray\MediaQuery\Annotation\DbQuery;

interface OrderItemQueryInterface
{
    /** @return list<OrderItemEntity> */
    #[DbQuery('order_items_by_order_no', factory: OrderItemFactory::class)]
    public function listByOrderNo(string $orderNo): array;
}
