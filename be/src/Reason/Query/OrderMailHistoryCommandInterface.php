<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryMailEntity;
use Ray\MediaQuery\Annotation\DbQuery;

interface OrderMailHistoryCommandInterface
{
    #[DbQuery('order_mail_history_insert')]
    public function insert(string $orderNo, OrderHistoryMailEntity $mailHistory): void;
}
