<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Override;
use PDO;

/**
 * PDO-backed admin id generator — Admin auth Phase B companion to
 * {@see \MyVendor\BeMart\Be\Reason\Query\SqlAdminCommand}.
 *
 * Pre-allocates the next id by querying `MAX(id)+1` on `dtb_member`.
 * Returns it as a string so that the Multi-Reason Being
 * {@see \MyVendor\BeMart\Be\Being\MemberCreating} can use the same
 * opaque-handle convention it uses with the Fake generator;
 * SqlAdminCommand::create then casts back to int when issuing the
 * INSERT with an explicit PK.
 *
 * Why not let AUTO_INCREMENT do the job? MemberCreating sets
 * `$this->adminId = $idGenerator->generate()` BEFORE the storage
 * write — so the adminId must be known at the time the Being builds
 * the downstream Final. Pre-allocating with MAX(id)+1 is the
 * simplest way to keep the generator interface unchanged. Same
 * shape as {@see SqlAddressIdGenerator} / {@see SqlBlockIdGenerator}
 * / {@see SqlPageIdGenerator}.
 *
 * Concurrency: a race against another writer would let two callers
 * see the same MAX(id)+1; the second INSERT would then collide on
 * the PK and raise. Acceptable for the test suite (each test runs
 * in its own transaction, no concurrent writers) and for an
 * admin-auth-Phase-B smoke. Production-grade allocation would use
 * an explicit `LAST_INSERT_ID(...)` sequence row or a dedicated
 * sequence table; out of scope for this step.
 *
 * DI: unbound in production (FakeAdminIdGenerator stays default).
 * Tests rebind via the SQL override module alongside SqlAdminCommand.
 */
final class SqlAdminIdGenerator implements AdminIdGeneratorInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function generate(): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT IFNULL(MAX(id), 0) + 1 AS next_id FROM dtb_member',
        );
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $next = $row === false ? 1 : (int) $row['next_id'];

        return (string) $next;
    }
}
