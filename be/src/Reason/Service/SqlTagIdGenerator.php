<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Override;
use PDO;

/**
 * PDO-backed tag id generator — Phase 2b companion to
 * {@see \MyVendor\BeMart\Be\Reason\Query\SqlTagStorage}.
 *
 * Pre-allocates the next id by querying `MAX(id)+1` on `dtb_tag`.
 * Returns it as a string so {@see \MyVendor\BeMart\Be\Final\TagCreated}
 * can use the same opaque-handle convention it uses with the Fake
 * generator; SqlTagStorage::put then casts back to int when issuing
 * the INSERT.
 *
 * Why not let AUTO_INCREMENT do the job? TagCreated sets
 * `$this->tagId = $entity->tagId` BEFORE the storage write — so the
 * tagId must be known at the time the Final builds the Entity.
 * Pre-allocating with MAX(id)+1 is the simplest way to keep the
 * generator interface unchanged.
 *
 * Concurrency: a race against another writer would let two callers
 * see the same MAX(id)+1; the second INSERT would then collide on
 * the PK and raise. This is acceptable for the test suite (each test
 * runs in its own transaction, no concurrent writers) and for a
 * Phase 2b smoke. Production-grade allocation would use an explicit
 * `LAST_INSERT_ID(...)` sequence row or a dedicated sequence table;
 * that hardening is out of scope for this step.
 *
 * DI: unbound in production (FakeTagIdGenerator stays default).
 * Tests rebind via the SQL override module alongside SqlTagStorage.
 */
final class SqlTagIdGenerator implements TagIdGeneratorInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function generate(): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT IFNULL(MAX(id), 0) + 1 AS next_id FROM dtb_tag',
        );
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $next = $row === false ? 1 : (int) $row['next_id'];

        return (string) $next;
    }
}
