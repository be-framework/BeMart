<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\AllocatedId;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Generates opaque address identifiers — mirrors
 * CustomerIdQueryInterface (Pilot 4). A dedicated interface lets
 * tests stub a deterministic id query per-case (e.g. for asserting
 * Location headers) without rebinding the customer-id query.
 */
interface AddressIdQueryInterface
{
    #[DbQuery('address_next_id')]
    public function next(): AllocatedId;
}
