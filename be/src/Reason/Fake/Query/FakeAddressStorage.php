<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Query;

use MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Entity\AddressEntity;
use Override;

/**
 * In-memory address book. Starts empty (no JSON fixture) — tests seed
 * by POSTing to the create endpoint. Singleton-bound so a request's
 * reads see its writes within the same Becoming chain.
 */
final class FakeAddressStorage implements AddressStorageInterface
{
    /** @var array<string, AddressEntity> keyed by addressId */
    private array $byId = [];

    /** @return list<AddressEntity> */
    #[Override]
    public function listByCustomer(string $customerId): array
    {
        $out = [];
        foreach ($this->byId as $address) {
            if ($address->customerId === $customerId) {
                $out[] = $address;
            }
        }

        return $out;
    }

    #[Override]
    public function getById(string $addressId): AddressEntity|null
    {
        return $this->byId[$addressId] ?? null;
    }

    #[Override]
    public function put(AddressEntity $address): void
    {
        $this->byId[$address->addressId] = $address;
    }

    #[Override]
    public function remove(string $addressId): void
    {
        unset($this->byId[$addressId]);
    }
}
