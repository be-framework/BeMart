<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use Override;

final class NoopInventoryAllocator implements InventoryAllocatorInterface
{
    #[Override]
    public function allocate(OrderEntity $preOrder): void
    {
    }
}
