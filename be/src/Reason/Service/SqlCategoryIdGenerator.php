<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Override;
use PDO;

/**
 * PDO-backed category id generator — Phase 2b companion to
 * {@see \MyVendor\BeMart\Be\Reason\Query\SqlCategoryStorage}.
 *
 * Pre-allocates the next id by querying `MAX(id)+1` on `dtb_category`.
 * Returns it as a string so {@see \MyVendor\BeMart\Be\Final\CategoryCreated}
 * can use the same opaque-handle convention it uses with the Fake
 * generator; SqlCategoryStorage::put then casts back to int when
 * issuing the INSERT.
 *
 * Why not let AUTO_INCREMENT do the job? CategoryCreated sets
 * `$this->categoryId = $entity->categoryId` BEFORE the storage write
 * — so the categoryId must be known at the time the Final builds the
 * Entity. Pre-allocating with MAX(id)+1 is the simplest way to keep
 * the generator interface unchanged. Same shape as
 * {@see SqlBlockIdGenerator} / {@see SqlPageIdGenerator} /
 * {@see SqlNewsIdGenerator} / {@see SqlTagIdGenerator} /
 * {@see SqlTaxRuleIdGenerator}.
 *
 * Concurrency: a race against another writer would let two callers
 * see the same MAX(id)+1; the second INSERT would then collide on
 * the PK and raise. Acceptable for the test suite (each test runs
 * in its own transaction) and for a Phase 2b smoke. Production-grade
 * allocation would use an explicit `LAST_INSERT_ID(...)` sequence row
 * or a dedicated sequence table; out of scope for this step.
 *
 * DI: unbound in production (FakeCategoryIdGenerator stays default).
 * Tests rebind via the SQL override module alongside
 * SqlCategoryStorage.
 */
final class SqlCategoryIdGenerator implements CategoryIdGeneratorInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function generate(): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT IFNULL(MAX(id), 0) + 1 AS next_id FROM dtb_category',
        );
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $next = $row === false ? 1 : (int) $row['next_id'];

        return (string) $next;
    }
}
