<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use DateTimeImmutable;
use MyVendor\BeMart\Be\Reason\Entity\PasswordResetTokenEntity;
use Override;
use PDO;

use function ctype_digit;

/**
 * Real PDO-backed password-reset-token storage — Phase 2b.
 *
 * Mirrors {@see FakePasswordResetTokenStorage} against the live EC-CUBE
 * 4.3 schema. Pure prepared statements: no Doctrine, no ORM.
 *
 * Backing-table decision — Option A (mirror EC-CUBE, no schema change):
 *   EC-CUBE 4.3 has NO separate password-reset-token table. The token
 *   lives as two columns directly on `dtb_customer`:
 *     - `reset_key`    varchar(255) NULL — the one-time token
 *     - `reset_expire` datetime     NULL — the token's expiry moment
 *   (verified in sql/schema/ec-cube-4.3-mysql-mysqldump.sql lines 256-257;
 *   `reset_expire` carries the Doctrine `DC2Type:datetimetz` comment but
 *   is a plain MySQL `datetime` column at the storage level).
 *
 *   So this storage does NOT own a table — it UPDATEs the two columns on
 *   the customer row. {@see SqlCustomerQuery} / {@see SqlCustomerCommand}
 *   project the SAME `dtb_customer` table but never touch `reset_key` /
 *   `reset_expire`, so there is no conflict — the surfaces are disjoint.
 *
 * Method semantics:
 *
 *   put(token)
 *     UPDATE dtb_customer SET reset_key = ?, reset_expire = ? WHERE id = ?
 *     The id is the token's customerId. Issuing a new token for a
 *     customer REPLACES the prior one (single-use, latest-wins) — a
 *     column UPDATE does this naturally, no DELETE-then-INSERT.
 *     `expiresAt` (a DateTimeImmutable) is formatted to the MySQL
 *     `datetime` literal `Y-m-d H:i:s`. A non-numeric customerId is a
 *     silent no-op — `dtb_customer.id` is an int PK and the BeMart
 *     issuer ({@see \MyVendor\BeMart\Be\Final\PasswordResetRequested})
 *     sources the id from a customer the query already resolved, so it
 *     is always numeric in practice; the guard is purely defensive,
 *     same shape as {@see SqlCustomerCommand}.
 *
 *   getByResetKey(key)
 *     SELECT id, reset_key, reset_expire FROM dtb_customer
 *       WHERE reset_key = ? LIMIT 1
 *     Reconstructs a {@see PasswordResetTokenEntity}. Returns the row
 *     REGARDLESS of expiry — the consumer
 *     ({@see \MyVendor\BeMart\Be\Final\PasswordResetCompleted}) performs
 *     its own `expiresAt < now` check on the Entity and raises the
 *     merged "wrong / expired / used" exception itself. This matches
 *     {@see FakePasswordResetTokenStorage::getByResetKey} exactly, which
 *     also returns the token without any expiry filtering. A NULL
 *     `reset_key` (a customer with no active token) never equals a
 *     non-NULL probe in SQL, so an arbitrary key misses cleanly.
 *
 *   delete(key)
 *     UPDATE dtb_customer SET reset_key = NULL, reset_expire = NULL
 *       WHERE reset_key = ?
 *     Single-use consumption. Idempotent: a key already cleared (or
 *     never issued) matches no row, so the statement is a harmless
 *     no-op — same silent-miss behavior as the Fake.
 *
 * DI is intentionally NOT wired in production
 * (FakePasswordResetTokenStorage remains the bound implementation). The
 * SQL impl is exercised via the test-only override in
 * AbstractResourceSqlTestCase.
 */
final class SqlPasswordResetTokenStorage implements PasswordResetTokenStorageInterface
{
    private const DATETIME_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function put(PasswordResetTokenEntity $token): void
    {
        if (! ctype_digit($token->customerId)) {
            // dtb_customer.id is an int PK — a non-numeric handle cannot
            // address a row. Silent no-op (defensive; the issuer always
            // sources a numeric id from a resolved customer).
            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE dtb_customer SET reset_key = :reset_key, '
            . 'reset_expire = :reset_expire WHERE id = :id',
        );
        $stmt->execute([
            ':id' => (int) $token->customerId,
            ':reset_key' => $token->resetKey,
            ':reset_expire' => $token->expiresAt->format(self::DATETIME_FORMAT),
        ]);
    }

    #[Override]
    public function getByResetKey(string $resetKey): PasswordResetTokenEntity|null
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, reset_key, reset_expire FROM dtb_customer '
            . 'WHERE reset_key = :reset_key LIMIT 1',
        );
        $stmt->execute([':reset_key' => $resetKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        // A row with reset_key set but reset_expire NULL should not
        // occur (put writes both, delete clears both), but guard
        // anyway: treat a missing expiry as already-expired so the
        // consumer's `expiresAt < now` check rejects it.
        $expiresAt = $row['reset_expire'] === null
            ? new DateTimeImmutable('-1 second')
            : new DateTimeImmutable((string) $row['reset_expire']);

        return new PasswordResetTokenEntity(
            customerId: (string) $row['id'],
            resetKey: (string) $row['reset_key'],
            expiresAt: $expiresAt,
        );
    }

    #[Override]
    public function delete(string $resetKey): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE dtb_customer SET reset_key = NULL, reset_expire = NULL '
            . 'WHERE reset_key = :reset_key',
        );
        $stmt->execute([':reset_key' => $resetKey]);
    }
}
