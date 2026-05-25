<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryEntity;
use MyVendor\BeMart\Be\Reason\Query\Factory\OrderHistoryFactory;
use Ray\MediaQuery\Annotation\DbQuery;

interface OrderHistoryQueryInterface
{
    #[DbQuery('order_history_by_order_no', factory: OrderHistoryFactory::class)]
    public function item(string $orderNo): ?OrderHistoryEntity;
}
