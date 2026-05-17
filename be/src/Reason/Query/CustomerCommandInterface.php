<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;

interface CustomerCommandInterface
{
    public function register(CustomerEntity $customer): void;
}
