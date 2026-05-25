<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use Override;

final class FakeCartQuery implements CartQueryInterface
{
    public function __construct(
        private readonly FakeCartStorage $storage,
    ) {
    }

    #[Override]
    public function byCartKey(string $cartKey): CartEntity|null
    {
        return $this->storage->get($cartKey);
    }
}
