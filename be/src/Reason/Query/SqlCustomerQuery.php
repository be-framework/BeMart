<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use Override;
use PDO;

/**
 * Real PDO-backed read-side Customer query — Phase 2a Step 2 smoke.
 *
 * First non-fake implementation against the live EC-CUBE 4.3 schema
 * (`dtb_customer`). Pure prepared statements: no Doctrine, no ORM.
 * Mapping follows the entity-vs-eccube.md diff report (CustomerEntity
 * is Grade A — 1:1 with dtb_customer, no schema delta needed).
 *
 * Notes on the mapping:
 * - `dtb_customer.id` is `int(10) unsigned`, but {@see CustomerEntity}
 *   models `customerId` as a string (opaque token). We cast on read.
 * - `dtb_customer.secret_key` is `NOT NULL UNIQUE`. CustomerEntity's
 *   `secretKey` is nullable. We treat the empty string sentinel as
 *   `null` on read (active customers may carry an empty secret).
 * - The table uses `utf8mb4_bin` collation, so `LIKE` is binary
 *   case-sensitive. This matches the Fake's `str_contains` behavior
 *   (the EC-CUBE admin grid is case-sensitive too).
 *
 * DI is intentionally NOT wired in Phase 2a; FakeCustomerQuery remains
 * the bound implementation. Phase 2b will swap the binding once all
 * read-side queries have a SQL counterpart.
 */
final class SqlCustomerQuery implements CustomerQueryInterface
{
    private const SELECT_COLUMNS = 'id, email, password, name01, name02, kana01, kana02, '
        . 'company_name, phone_number, postal_code, pref_id, addr01, addr02, '
        . 'birth, sex_id, job_id, customer_status_id, secret_key';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function findByEmail(string $email): CustomerEntity|null
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_customer WHERE email = :email LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[Override]
    public function findBySecretKey(string $secretKey): CustomerEntity|null
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_customer WHERE secret_key = :secret_key LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':secret_key' => $secretKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[Override]
    public function findById(string $customerId): CustomerEntity|null
    {
        // CustomerEntity::customerId is string but dtb_customer.id is int.
        // Reject non-numeric ids early so we don't issue garbage queries.
        if (! ctype_digit($customerId)) {
            return null;
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_customer WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int) $customerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    /**
     * @return list<CustomerEntity>
     */
    #[Override]
    public function search(?string $nameKeyword, ?string $emailKeyword, int $limit = 50): array
    {
        $where = [];
        $params = [];

        if ($nameKeyword !== null && $nameKeyword !== '') {
            // With ATTR_EMULATE_PREPARES=false, a named placeholder may be
            // bound only once per statement — so each LIKE branch needs its
            // own parameter even though the value is identical.
            $where[] = '(name01 LIKE :name_kw_a OR name02 LIKE :name_kw_b OR company_name LIKE :name_kw_c)';
            $pattern = '%' . $this->escapeLike($nameKeyword) . '%';
            $params[':name_kw_a'] = $pattern;
            $params[':name_kw_b'] = $pattern;
            $params[':name_kw_c'] = $pattern;
        }

        if ($emailKeyword !== null && $emailKeyword !== '') {
            $where[] = 'email LIKE :email_kw';
            $params[':email_kw'] = '%' . $this->escapeLike($emailKeyword) . '%';
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_customer';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        // ORDER BY id keeps results deterministic for tests. LIMIT is
        // inlined (safe — $limit is typed `int`) because PDO with
        // emulated prepares quotes a bound LIMIT as a string and trips
        // MySQL's parser.
        $sql .= ' ORDER BY id ASC LIMIT ' . $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrate($row);
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): CustomerEntity
    {
        $secretKey = (string) $row['secret_key'];

        return new CustomerEntity(
            customerId: (string) $row['id'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password'],
            name01: (string) $row['name01'],
            name02: (string) $row['name02'],
            kana01: $row['kana01'] === null ? null : (string) $row['kana01'],
            kana02: $row['kana02'] === null ? null : (string) $row['kana02'],
            companyName: $row['company_name'] === null ? null : (string) $row['company_name'],
            phoneNumber: $row['phone_number'] === null ? null : (string) $row['phone_number'],
            postalCode: $row['postal_code'] === null ? null : (string) $row['postal_code'],
            pref: $row['pref_id'] === null ? null : (int) $row['pref_id'],
            addr01: $row['addr01'] === null ? null : (string) $row['addr01'],
            addr02: $row['addr02'] === null ? null : (string) $row['addr02'],
            birth: $row['birth'] === null ? null : (string) $row['birth'],
            sex: $row['sex_id'] === null ? null : (int) $row['sex_id'],
            job: $row['job_id'] === null ? null : (int) $row['job_id'],
            initialPoint: 0,
            customerStatus: (int) $row['customer_status_id'],
            secretKey: $secretKey === '' ? null : $secretKey,
        );
    }

    /**
     * Escape `%` and `_` so substring keywords can't smuggle wildcards.
     * Uses `\` as the escape character (MySQL default for LIKE).
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
