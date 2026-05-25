<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use Override;
use PDO;

use function bin2hex;
use function ctype_digit;
use function random_bytes;

/**
 * Real PDO-backed Customer write-side — Phase 2b.
 *
 * Mirrors {@see FakeCustomerCommand} against the live EC-CUBE 4.3
 * schema (`dtb_customer`). The companion read side
 * {@see SqlCustomerQuery} already exists (Phase 2a Step 2); this class
 * writes the SAME column↔field projection so a read-after-write
 * round-trips. Pure prepared statements: no Doctrine, no ORM.
 *
 * Column mapping — identical to {@see SqlCustomerQuery::hydrate}:
 *   id ← customerId (cast int), email, password ← passwordHash,
 *   name01, name02, kana01, kana02, company_name ← companyName,
 *   phone_number ← phoneNumber, postal_code ← postalCode,
 *   pref_id ← pref, addr01, addr02, birth, sex_id ← sex, job_id ← job,
 *   customer_status_id ← customerStatus, secret_key ← secretKey,
 *   point ← initialPoint.
 *
 * Columns NOT modelled by {@see CustomerEntity}, defaulted on INSERT:
 *   - `salt` → NULL. EC-CUBE 4.x bcrypt embeds the salt inside the
 *     password hash; the column is vestigial.
 *   - `buy_times` / `buy_total` → 0 / 0.00. A fresh customer has no
 *     order history (the EC-CUBE installer default).
 *   - `country_id` → NULL. Out of scope for the BeMart slice.
 *   - `first_buy_date` / `last_buy_date` / `note` / `reset_key` /
 *     `reset_expire` → NULL. No history / admin note / in-flight reset
 *     on a fresh row.
 *   - `discriminator_type` → 'customer' (Doctrine single-table
 *     discriminator value EC-CUBE writes for Eccube\Entity\Customer,
 *     same convention {@see SqlAdminCommand} uses with 'member').
 *   - `create_date` / `update_date` → NOW() on insert; only
 *     `update_date` on UPDATE / activate / password-reset (matches the
 *     Doctrine Timestampable behavior EC-CUBE relies on).
 *
 * `secret_key` is `NOT NULL UNIQUE` (verified in
 * sql/schema/ec-cube-4.3-mysql-mysqldump.sql). {@see CustomerEntity}'s
 * `secretKey` is nullable: a freshly-registered active customer (built
 * by {@see \MyVendor\BeMart\Be\Final\CustomerRegistered}) carries
 * `null`. Since the column rejects NULL, `register` writes the
 * caller-supplied key when present, otherwise generates a unique
 * 32-char hex token. {@see SqlCustomerQuery} coerces an empty
 * `secret_key` back to `null` on read, so an active customer reads
 * back as `secretKey === null` either way.
 *
 * Pre-allocated id discipline (`register`):
 *   customerId is pre-allocated by {@see \MyVendor\BeMart\Be\Reason\Service\SqlCustomerIdGenerator}
 *   before the registration Being runs ({@see \MyVendor\BeMart\Be\Being\CustomerRegistering}
 *   sets `$this->customerId = $idGenerator->generate()`), so this
 *   command receives an id-bearing entity. A non-numeric id is
 *   rejected as a silent no-op — the Fake generator emits 32-char hex
 *   which would otherwise collide with the int PK; production must
 *   rebind to the SQL generator before swapping in this storage. Same
 *   convention as {@see SqlAdminCommand}.
 *
 * Activation (`activate`, status 1→2):
 *   Flips `customer_status_id` to 2 and leaves `secret_key` intact.
 *   The interface docblock predates the SQL backing and says "clears
 *   the secretKey"; the FakeCustomerStorage nulls it. The SQL impl
 *   cannot null it (NOT NULL) — and writing the empty-string sentinel
 *   for every activated customer would collide on the UNIQUE index the
 *   moment a second customer activates. EC-CUBE keeps the key after
 *   activation, so this impl keeps it too. This is invisible to the
 *   migration contract: {@see \MyVendor\BeMart\Be\Final\CustomerActivated}'s
 *   public surface exposes only customerId / email / customerStatus,
 *   never secretKey. Idempotent: re-activating a status-2 customer is a
 *   harmless no-op UPDATE (the WHERE matches but sets the same value).
 *
 * DI is intentionally NOT wired in production (FakeCustomerCommand
 * remains the bound implementation). The SQL impl is exercised via the
 * test-only override in AbstractResourceSqlTestCase.
 */
final class SqlCustomerCommand implements CustomerCommandInterface
{
    private const DISCRIMINATOR = 'customer';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function register(CustomerEntity $customer): void
    {
        if (! ctype_digit($customer->customerId)) {
            // Defensive: a non-numeric id we cannot persist with an
            // explicit PK. The Fake generator emits 32-char hex;
            // production must rebind to SqlCustomerIdGenerator before
            // swapping in this storage.
            return;
        }

        // secret_key is NOT NULL UNIQUE. A registered active customer
        // carries a null secretKey — supply a fresh unique token so the
        // INSERT satisfies the constraint. SqlCustomerQuery treats a
        // non-empty key as a value and an empty one as null; either
        // way an active customer reads back with secretKey === null
        // only when the stored value is the empty sentinel, so we keep
        // the generated token here (harmless — never surfaced).
        $secretKey = $customer->secretKey ?? bin2hex(random_bytes(16));

        $sql = 'INSERT INTO dtb_customer '
            . '(id, customer_status_id, sex_id, job_id, country_id, pref_id, '
            . 'name01, name02, kana01, kana02, company_name, postal_code, '
            . 'addr01, addr02, email, phone_number, birth, password, salt, '
            . 'secret_key, first_buy_date, last_buy_date, buy_times, '
            . 'buy_total, note, reset_key, reset_expire, point, '
            . 'create_date, update_date, discriminator_type) '
            . 'VALUES (:id, :customer_status_id, :sex_id, :job_id, NULL, :pref_id, '
            . ':name01, :name02, :kana01, :kana02, :company_name, :postal_code, '
            . ':addr01, :addr02, :email, :phone_number, :birth, :password, NULL, '
            . ':secret_key, NULL, NULL, 0, '
            . '0, NULL, NULL, NULL, :point, '
            . 'NOW(), NOW(), :discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => (int) $customer->customerId,
            ':customer_status_id' => $customer->customerStatus,
            ':sex_id' => $customer->sex,
            ':job_id' => $customer->job,
            ':pref_id' => $customer->pref,
            ':name01' => $customer->name01,
            ':name02' => $customer->name02,
            ':kana01' => $customer->kana01,
            ':kana02' => $customer->kana02,
            ':company_name' => $customer->companyName,
            ':postal_code' => $customer->postalCode,
            ':addr01' => $customer->addr01,
            ':addr02' => $customer->addr02,
            ':email' => $customer->email,
            ':phone_number' => $customer->phoneNumber,
            ':birth' => $customer->birth,
            ':password' => $customer->passwordHash,
            ':secret_key' => $secretKey,
            ':point' => $customer->initialPoint,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    #[Override]
    public function activate(string $customerId): void
    {
        if (! ctype_digit($customerId)) {
            // Silent no-op on a non-numeric id — same shape as the Fake
            // which returns without raising when the id is missing.
            return;
        }

        // Flip status to 2 (Active). secret_key is intentionally NOT
        // cleared — it is NOT NULL UNIQUE and EC-CUBE keeps the key
        // after activation; nulling it is impossible and emptying it
        // would collide on the UNIQUE index. Idempotent: re-activating
        // a status-2 row sets the same value (a harmless no-op UPDATE).
        $stmt = $this->pdo->prepare(
            'UPDATE dtb_customer SET customer_status_id = 2, '
            . 'update_date = NOW() WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $customerId]);
    }

    #[Override]
    public function update(CustomerEntity $customer): void
    {
        if (! ctype_digit($customer->customerId)) {
            return;
        }

        // secret_key is NOT NULL — keep the existing key when the
        // entity carries a null (the caller built it from the persisted
        // state, so a null here means "active customer, no token to
        // re-write"). An empty-string sentinel keeps the UPDATE total
        // without ever colliding on the UNIQUE index because the
        // WHERE id pins exactly one row.
        $secretKey = $customer->secretKey ?? '';

        $sql = 'UPDATE dtb_customer SET '
            . 'email = :email, '
            . 'password = :password, '
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
            . 'birth = :birth, '
            . 'sex_id = :sex_id, '
            . 'job_id = :job_id, '
            . 'customer_status_id = :customer_status_id, '
            . 'secret_key = :secret_key, '
            . 'point = :point, '
            . 'update_date = NOW() '
            . 'WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => (int) $customer->customerId,
            ':email' => $customer->email,
            ':password' => $customer->passwordHash,
            ':name01' => $customer->name01,
            ':name02' => $customer->name02,
            ':kana01' => $customer->kana01,
            ':kana02' => $customer->kana02,
            ':company_name' => $customer->companyName,
            ':phone_number' => $customer->phoneNumber,
            ':postal_code' => $customer->postalCode,
            ':pref_id' => $customer->pref,
            ':addr01' => $customer->addr01,
            ':addr02' => $customer->addr02,
            ':birth' => $customer->birth,
            ':sex_id' => $customer->sex,
            ':job_id' => $customer->job,
            ':customer_status_id' => $customer->customerStatus,
            ':secret_key' => $secretKey,
            ':point' => $customer->initialPoint,
        ]);
    }

    #[Override]
    public function updatePassword(string $customerId, string $passwordHash): void
    {
        if (! ctype_digit($customerId)) {
            return;
        }

        // Single-column UPDATE — the narrow surface is the whole point
        // of having this method vs `update()`. The password-reset path
        // cannot reach unrelated fields (mass-assignment safety).
        $stmt = $this->pdo->prepare(
            'UPDATE dtb_customer SET password = :password, '
            . 'update_date = NOW() WHERE id = :id',
        );
        $stmt->execute([
            ':id' => (int) $customerId,
            ':password' => $passwordHash,
        ]);
    }
}
