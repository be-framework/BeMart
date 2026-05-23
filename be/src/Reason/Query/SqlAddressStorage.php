<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\AddressEntity;
use Override;

use function ctype_digit;

final class SqlAddressStorage implements AddressStorageInterface
{
    public function __construct(private readonly InternalDbQueryInterface $db) {}

    /** @return list<AddressEntity> */
    #[Override]
    public function listByCustomer(string $customerId): array
    {
        if (! ctype_digit($customerId)) {
            return [];
        }
        return array_map($this->hydrate(...), $this->db->address_list_by_customer(customerId: (int) $customerId));
    }

    #[Override]
    public function getById(string $addressId): AddressEntity|null
    {
        if (! ctype_digit($addressId)) {
            return null;
        }
        $row = $this->db->address_get(id: (int) $addressId);
        return $row === null ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(AddressEntity $address): void
    {
        if (! ctype_digit($address->addressId) || ! ctype_digit($address->customerId)) {
            return;
        }
        $id = (int) $address->addressId;
        if ($this->db->address_exists(id: $id) === null) {
            $this->db->address_insert(
                id: $id,
                customerId: (int) $address->customerId,
                name01: $address->name01,
                name02: $address->name02,
                kana01: $address->kana01,
                kana02: $address->kana02,
                companyName: $address->companyName,
                phoneNumber: $address->phoneNumber,
                postalCode: $address->postalCode,
                prefId: $address->pref === 0 ? null : $address->pref,
                addr01: $address->addr01,
                addr02: $address->addr02,
            );

            return;
        }

        $this->db->address_update(
            id: $id,
            customerId: (int) $address->customerId,
            name01: $address->name01,
            name02: $address->name02,
            kana01: $address->kana01,
            kana02: $address->kana02,
            companyName: $address->companyName,
            phoneNumber: $address->phoneNumber,
            postalCode: $address->postalCode,
            prefId: $address->pref === 0 ? null : $address->pref,
            addr01: $address->addr01,
            addr02: $address->addr02,
        );
    }

    #[Override]
    public function remove(string $addressId): void
    {
        if (ctype_digit($addressId)) {
            $this->db->address_delete(id: (int) $addressId);
        }
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): AddressEntity
    {
        return new AddressEntity(
            addressId: (string) (int) $row['id'],
            customerId: $row['customer_id'] === null ? '' : (string) (int) $row['customer_id'],
            name01: (string) $row['name01'],
            name02: (string) $row['name02'],
            kana01: $row['kana01'] === null ? null : (string) $row['kana01'],
            kana02: $row['kana02'] === null ? null : (string) $row['kana02'],
            companyName: $row['company_name'] === null ? null : (string) $row['company_name'],
            phoneNumber: $row['phone_number'] === null ? null : (string) $row['phone_number'],
            postalCode: $row['postal_code'] === null ? '' : (string) $row['postal_code'],
            pref: $row['pref_id'] === null ? 0 : (int) $row['pref_id'],
            addr01: $row['addr01'] === null ? '' : (string) $row['addr01'],
            addr02: $row['addr02'] === null ? '' : (string) $row['addr02'],
            prefName: isset($row['pref_name']) && $row['pref_name'] !== null ? (string) $row['pref_name'] : null,
        );
    }
}
