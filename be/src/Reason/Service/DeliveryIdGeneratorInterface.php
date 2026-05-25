<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Query\Result\AllocatedId;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Generates opaque Delivery identifiers (Wave 9θ).
 */
interface DeliveryIdGeneratorInterface
{
    #[DbQuery('delivery_next_id')]
    public function next(): AllocatedId;
}
