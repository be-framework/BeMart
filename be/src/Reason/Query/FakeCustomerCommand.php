<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use Override;

final class FakeCustomerCommand implements CustomerCommandInterface
{
    public function __construct(
        private readonly FakeCustomerStorage $storage,
    ) {
    }

    public function register(CustomerEntity $customer): void
    {
        $this->storage->put($customer);
    }

    #[Override]
    public function activate(string $customerId): void
    {
        $this->storage->activate($customerId);
    }
}
