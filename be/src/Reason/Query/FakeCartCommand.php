<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use Override;

final class FakeCartCommand implements CartCommandInterface
{
    public function __construct(
        private readonly FakeCartStorage $storage,
    ) {
    }

    #[Override]
    public function save(CartEntity $cart): void
    {
        $this->storage->put($cart);
    }
}
