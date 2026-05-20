<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\AddressEntity;
use Override;
use PDO;

/**
 * Real PDO-backed Address storage — Phase 2b.
 *
 * Mirrors {@see FakeAddressStorage} against the live EC-CUBE 4.3 schema
 * (`dtb_customer_address`). The mapping is mostly straightforward but
 * has four hydrate-time coercions because AddressEntity is stricter
 * than the column nullability:
 *
 *   - `id` is `int unsigned`, AddressEntity::addressId is `string`
 *     → cast `(string) (int)` on read, parse with `ctype_digit` on
 *     write. A non-numeric incoming addressId (e.g. a leftover hex
 *     from the Fake generator) is rejected: getById returns null,
 *     put no-ops, remove no-ops.
 *   - `customer_id` is nullable, AddressEntity::customerId is required
 *     `string` → CustomerAddressCreated always sets it from the
 *     session, so in practice rows we write carry a customer_id; we
 *     surface NULL as empty string on read for defensive hydration.
 *   - `postal_code` / `addr01` / `addr02` are nullable, the Entity
 *     fields are non-null `string` → coerce NULL to empty string.
 *   - `pref_id` is nullable, AddressEntity::pref is `int` → coerce
 *     NULL to 0. Same convention as
 *     {@see SqlCustomerQuery::hydrate} for missing master-table refs.
 *
 * Upsert convention (`put`):
 *   addressId is pre-allocated by {@see SqlAddressIdGenerator} before
 *   `put` is called (the Final assigns `$entity->addressId` from the
 *   generator's output, so the storage receives an id-bearing entity).
 *   `put` probes `SELECT 1 WHERE id = ?`; hit → UPDATE, miss → INSERT
 *   with the explicit id. discriminator_type is 'customeraddress'
 *   (the value EC-CUBE writes — see
 *   `tools/ec-cube-source/var/cache/install/doctrine/orm/default_metadata.php`).
 *
 * Timestamps: NOW() on insert for both `create_date` and `update_date`;
 * NOW() on `update_date` only for updates (matches the Doctrine
 * Timestampable behavior EC-CUBE relies on).
 *
 * DI is intentionally NOT wired in Phase 2b; FakeAddressStorage remains
 * the production-bound implementation. The SQL impl is exercised via
 * the test-only override in AbstractResourceSqlTestCase.
 */
final class SqlAddressStorage implements AddressStorageInterface
{
    private const SELECT_COLUMNS = 'ca.id, ca.customer_id, ca.name01, ca.name02, '
        . 'ca.kana01, ca.kana02, ca.company_name, ca.phone_number, '
        . 'ca.postal_code, ca.pref_id, ca.addr01, ca.addr02, '
        // Phase 3 enrichment — the prefecture DISPLAY name. mtb_pref is a
        // nullable FK target left EMPTY in the structure-only schema dump,
        // so this is a LEFT JOIN: a missing match degrades to NULL (the
        // hydrator coalesces to null prefName), it does NOT drop the row.
        . 'pref.name AS pref_name';

    private const FROM_JOIN = 'FROM dtb_customer_address ca '
        . 'LEFT JOIN mtb_pref pref ON pref.id = ca.pref_id';

    private const DISCRIMINATOR = 'customeraddress';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<AddressEntity> */
    #[Override]
    public function listByCustomer(string $customerId): array
    {
        if (! ctype_digit($customerId)) {
            return [];
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' ' . self::FROM_JOIN . ' '
            . 'WHERE ca.customer_id = :customer_id ORDER BY ca.id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':customer_id' => (int) $customerId]);

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrate($row);
        }

        return $out;
    }

    #[Override]
    public function getById(string $addressId): AddressEntity|null
    {
        if (! ctype_digit($addressId)) {
            // Non-numeric ids (e.g. a leftover hex from a Fake-style
            // generator) can never match an int PK. Surface as miss so
            // CustomerAddressUpdated / CustomerAddressDeleted raise
            // their normal 404 instead of throwing a PDO exception.
            return null;
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' ' . self::FROM_JOIN . ' '
            . 'WHERE ca.id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int) $addressId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(AddressEntity $address): void
    {
        if (! ctype_digit($address->addressId) || ! ctype_digit($address->customerId)) {
            // Defensive: a non-numeric id we cannot persist. The Fake
            // generator emits hex; production must rebind to a numeric
            // generator before swapping in this storage.
            return;
        }

        $id = (int) $address->addressId;
        $customerId = (int) $address->customerId;

        $existsStmt = $this->pdo->prepare(
            'SELECT 1 FROM dtb_customer_address WHERE id = :id LIMIT 1',
        );
        $existsStmt->execute([':id' => $id]);
        $exists = $existsStmt->fetchColumn() !== false;

        if ($exists) {
            $sql = 'UPDATE dtb_customer_address SET '
                . 'customer_id = :customer_id, '
                . 'name01 = :name01, '
                . 'name02 = :name02, '
                . 'kana01 = :kana01, '
                . 'kana02 = :kana02, '
                . 'company_name = :company_name, '
                . 'phone_number = :phone_number, '
                . 'postal_code = :postal_code, '
                . 'pref_id = :pref_id, '
                . 'addr01 = :addr01, '
                . 'addr02 = :addr02, '
                . 'update_date = NOW() '
                . 'WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':customer_id' => $customerId,
                ':name01' => $address->name01,
                ':name02' => $address->name02,
                ':kana01' => $address->kana01,
                ':kana02' => $address->kana02,
                ':company_name' => $address->companyName,
                ':phone_number' => $address->phoneNumber,
                ':postal_code' => $address->postalCode,
                ':pref_id' => $address->pref === 0 ? null : $address->pref,
                ':addr01' => $address->addr01,
                ':addr02' => $address->addr02,
            ]);

            return;
        }

        $sql = 'INSERT INTO dtb_customer_address '
            . '(id, customer_id, name01, name02, kana01, kana02, company_name, '
            . 'phone_number, postal_code, pref_id, addr01, addr02, '
            . 'create_date, update_date, discriminator_type) '
            . 'VALUES (:id, :customer_id, :name01, :name02, :kana01, :kana02, '
            . ':company_name, :phone_number, :postal_code, :pref_id, '
            . ':addr01, :addr02, NOW(), NOW(), :discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':customer_id' => $customerId,
            ':name01' => $address->name01,
            ':name02' => $address->name02,
            ':kana01' => $address->kana01,
            ':kana02' => $address->kana02,
            ':company_name' => $address->companyName,
            ':phone_number' => $address->phoneNumber,
            ':postal_code' => $address->postalCode,
            ':pref_id' => $address->pref === 0 ? null : $address->pref,
            ':addr01' => $address->addr01,
            ':addr02' => $address->addr02,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    #[Override]
    public function remove(string $addressId): void
    {
        if (! ctype_digit($addressId)) {
            // Silent no-op on a non-numeric id — same shape as the Fake
            // which `unset()`s a missing key without raising.
            return;
        }

        $stmt = $this->pdo->prepare(
            'DELETE FROM dtb_customer_address WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $addressId]);
    }

    /**
     * @param array<string, mixed> $row
     */
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
            // Phase 3 enrichment — the prefecture display name from the
            // mtb_pref JOIN; NULL when pref_id is unset or the master
            // row is absent (structure-only dump).
            prefName: isset($row['pref_name']) && $row['pref_name'] !== null
                ? (string) $row['pref_name']
                : null,
        );
    }
}
