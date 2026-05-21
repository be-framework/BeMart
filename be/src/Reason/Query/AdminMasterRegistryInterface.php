<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

/**
 * Routes the two generic admin-list transitions (`doSortNoMove` /
 * `doToggleVisible`) to the storage that owns the named master.
 *
 * EC-CUBE exposes a per-master controller action for each of these
 * (admin_setting_shop_payment_sort_no_move, admin_product_tag_visible,
 * …). BeMart folds them into one abstract transition keyed by a
 * `masterType` discriminator; this registry is the dispatcher that
 * turns the discriminator back into a concrete storage call.
 *
 * Capability matrix (mirrors the EC-CUBE 4.3 schema columns):
 *   - reorder:    payment / delivery / tag / className / classCategory
 *                 (these tables have a `sort_no` column)
 *   - setVisible: payment / delivery / classCategory / news
 *                 (these tables have a `visible` column)
 *
 * The registry only depends on the storage *interfaces*, so the single
 * implementation works under both the Fake and the SQL bindings.
 *
 * Method contract:
 *   - `supportsReorder` / `supportsVisible` — capability probe; false
 *     for a known master that lacks the column.
 *   - `rowExists` — true when the id resolves to a row in the master.
 *   - `reorder` / `setVisible` — perform the write. Callers are
 *     expected to gate on `rowExists` first (the per-master storages
 *     no-op on a miss, so a missing row is a silent no-op here too —
 *     the explicit existence check is what produces the 404).
 */
interface AdminMasterRegistryInterface
{
    /**
     * @return list<array{value: string, label: string, table: string}>
     */
    public function listMasterTypes(): array;

    /**
     * @return list<array{id: string, name: string}>
     */
    public function listRows(string $masterType): array;

    public function supportsReorder(string $masterType): bool;

    public function supportsVisible(string $masterType): bool;

    public function rowExists(string $masterType, string $rowId): bool;

    public function reorder(string $masterType, string $rowId, int $sortNo): void;

    public function setVisible(string $masterType, string $rowId, bool $visible): void;
}
