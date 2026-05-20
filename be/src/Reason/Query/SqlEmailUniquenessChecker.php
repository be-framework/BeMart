<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException;
use Override;
use PDO;

/**
 * Real PDO-backed email-uniqueness guard — Phase 2b companion to
 * {@see SqlCustomerCommand}, the natural write-side partner of the
 * customer-registration / profile-update flows.
 *
 * Mirrors {@see FakeEmailUniquenessChecker} against the live EC-CUBE
 * 4.3 schema (`dtb_customer`). The Fake delegates to
 * {@see FakeCustomerStorage::existsByEmail}, which matches ANY row with
 * that email — not only status-2 (active) rows. This SQL impl mirrors
 * that exactly: a single `SELECT 1 ... WHERE email = ?` existence
 * probe.
 *
 * Why "any row" and not "active only" despite the interface docblock
 * saying "no ACTIVE customer": withdrawal rewrites the email to
 * `withdrawn-{id}@example.invalid` (the RFC-2606 reserved TLD), so a
 * withdrawn customer never occupies the original address. A
 * provisional (status-1) customer DOES still hold the address, and
 * EC-CUBE rejects a second registration against a not-yet-activated
 * email — so "any row" is the correct, contract-preserving behavior.
 *
 * The `dtb_customer` table uses `utf8mb4_bin` collation, so the
 * equality comparison is binary / case-sensitive — identical to the
 * Fake's array-key lookup.
 *
 * DI is intentionally NOT wired in production (FakeEmailUniquenessChecker
 * remains the bound implementation). The SQL impl is exercised via the
 * test-only override in AbstractResourceSqlTestCase.
 */
final class SqlEmailUniquenessChecker implements EmailUniquenessCheckerInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function ensureUnique(string $email): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM dtb_customer WHERE email = :email LIMIT 1',
        );
        $stmt->execute([':email' => $email]);

        if ($stmt->fetchColumn() !== false) {
            throw new EmailAlreadyRegisteredException();
        }
    }
}
