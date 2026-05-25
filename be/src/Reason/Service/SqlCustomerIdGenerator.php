<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Override;
use PDO;

/**
 * PDO-backed customer id generator — Phase 2b companion to
 * {@see \MyVendor\BeMart\Be\Reason\Query\SqlCustomerCommand}.
 *
 * Pre-allocates the next id by querying `MAX(id)+1` on `dtb_customer`.
 * Returns it as a string so the Multi-Reason Being
 * {@see \MyVendor\BeMart\Be\Being\CustomerRegistering} keeps the same
 * opaque-handle convention it uses with the Fake generator;
 * SqlCustomerCommand::register then casts back to int when issuing the
 * INSERT with an explicit PK.
 *
 * Why not let AUTO_INCREMENT do the job? CustomerRegistering sets
 * `$this->customerId = $idGenerator->generate()` BEFORE the storage
 * write — so the customerId must be known at the time the Being builds
 * the downstream Final ({@see \MyVendor\BeMart\Be\Final\CustomerRegistered}).
 * Pre-allocating with MAX(id)+1 keeps the generator interface
 * unchanged. Same shape as {@see SqlAdminIdGenerator}.
 *
 * Concurrency: a race against another writer would let two callers see
 * the same MAX(id)+1; the second INSERT would then collide on the PK
 * and raise. Acceptable for the test suite (each test runs in its own
 * transaction, no concurrent writers). Production-grade allocation
 * would use a sequence row; out of scope for this step.
 *
 * DI: unbound in production (FakeCustomerIdGenerator stays default).
 * Tests rebind via the SQL override module alongside SqlCustomerCommand.
 */
final class SqlCustomerIdGenerator implements CustomerIdGeneratorInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function generate(): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT IFNULL(MAX(id), 0) + 1 AS next_id FROM dtb_customer',
        );
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $next = $row === false ? 1 : (int) $row['next_id'];

        return (string) $next;
    }
}
