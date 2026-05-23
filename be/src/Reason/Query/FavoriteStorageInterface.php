<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\FavoriteEntity;
use MyVendor\BeMart\Be\Reason\Query\Result\FavoritePresence;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Customer favorites — unified Query + Command (Pilot 13 first
 * iteration). Phase 2 can split into FavoriteQuery / FavoriteCommand
 * when the workload demands CQRS; for now both reads and writes are
 * trivial enough to share one interface.
 *
 *   - add(customerId, productCode): idempotent — duplicate add is a no-op
 *   - has(customerId, productCode): exists check
 *   - listByCustomer(customerId): list for the customer's favorites view
 *   - remove(customerId, productCode): for the future doRemoveFavorite pilot
 */
interface FavoriteStorageInterface
{
    #[DbQuery('favorite_add')]
    public function add(FavoriteEntity $favorite): void;

    #[DbQuery('favorite_has')]
    public function has(string $customerId, string $productCode): FavoritePresence;

    /** @return list<FavoriteEntity> */
    #[DbQuery('favorite_list', factory: FavoriteEntity::class)]
    public function listByCustomer(string $customerId): array;

    #[DbQuery('favorite_remove')]
    public function remove(string $customerId, string $productCode): void;
}
