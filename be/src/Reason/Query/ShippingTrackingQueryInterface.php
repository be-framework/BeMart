<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\ShippingTrackingNumber;
use Ray\MediaQuery\Annotation\DbQuery;

interface ShippingTrackingQueryInterface
{
    #[DbQuery('shipping_tracking_by_order_no')]
    public function item(string $orderNo): ShippingTrackingNumber;
}
