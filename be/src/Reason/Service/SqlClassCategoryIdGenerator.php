<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Override;
use PDO;

/**
 * PDO-backed class-category id generator — Phase 2b companion to
 * {@see \MyVendor\BeMart\Be\Reason\Query\SqlClassCategoryStorage}.
 *
 * Pre-allocates the next id by querying `MAX(id)+1` on
 * `dtb_class_category`. Returns it as a string so the
 * ClassCategory-create Final can use the same opaque-handle convention
 * it uses with the Fake generator; SqlClassCategoryStorage::put then
 * casts back to int when issuing the INSERT.
 *
 * Why not let AUTO_INCREMENT do the job? The create Final sets
 * `$entity->classCategoryId` from the generator output BEFORE the
 * storage write — so the classCategoryId must be known at the time the
 * Final builds the Entity. Pre-allocating with MAX(id)+1 is the
 * simplest way to keep the generator interface unchanged. Same shape as
 * {@see SqlClassNameIdGenerator} / {@see SqlCategoryIdGenerator}.
 *
 * Concurrency: a race against another writer would let two callers see
 * the same MAX(id)+1; the second INSERT would then collide on the PK
 * and raise. Acceptable for the test suite (each test runs in its own
 * transaction) and for a Phase 2b smoke. Production-grade allocation
 * would use an explicit sequence row; out of scope for this step.
 *
 * DI: unbound in production (FakeClassCategoryIdGenerator stays
 * default). Tests rebind via the SQL override module alongside
 * SqlClassCategoryStorage.
 */
final class SqlClassCategoryIdGenerator implements ClassCategoryIdGeneratorInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function generate(): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT IFNULL(MAX(id), 0) + 1 AS next_id FROM dtb_class_category',
        );
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $next = $row === false ? 1 : (int) $row['next_id'];

        return (string) $next;
    }
}
