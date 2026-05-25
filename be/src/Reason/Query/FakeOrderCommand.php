<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use Override;

final class FakeOrderCommand implements OrderCommandInterface
{
    public function __construct(
        private readonly FakeFinalizedOrderStorage $storage,
    ) {
    }

    #[Override]
    public function register(FinalizedOrderEntity $order): void
    {
        $this->storage->put($order);
    }
}
