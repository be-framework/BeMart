<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ShippingAddressEntity;
use Override;

use function array_values;

/**
 * In-memory fake for {@see ShippingAddressStorageInterface}.
 *
 * Singleton-bound so a request's POST/PUT is visible to a subsequent
 * GET inside the same test (same convention as
 * {@see FakeFinalizedOrderStorage} et al.).
 */
final class FakeShippingAddressStorage implements ShippingAddressStorageInterface
{
    /** @var array<string, ShippingAddressEntity> */
    private array $addresses = [];

    #[Override]
    public function getByOrderNo(string $orderNo): ShippingAddressEntity|null
    {
        return $this->addresses[$orderNo] ?? null;
    }

    #[Override]
    public function put(ShippingAddressEntity $address): void
    {
        $this->addresses[$address->orderNo] = $address;
    }

    /** @return list<ShippingAddressEntity> */
    #[Override]
    public function listAll(): array
    {
        return array_values($this->addresses);
    }
}
