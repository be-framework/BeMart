<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Query\Result\CartSaveResult;
use Override;

final class FakeCartCommand implements CartCommandInterface
{
    public function __construct(
        private readonly FakeCartStorage $storage,
    ) {
    }

    #[Override]
    public function save(CartEntity $cart): CartSaveResult
    {
        $this->storage->put($cart);

        return new CartSaveResult(true);
    }

    #[Override]
    public function clearByPreOrderId(string $preOrderId): void
    {
        $this->storage->removeByPreOrderId($preOrderId);
    }

    #[Override]
    public function clearBySessionPrefix(string $sessionPrefix): void
    {
        $this->storage->removeBySessionPrefix($sessionPrefix);
    }
}
