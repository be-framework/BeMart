<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;

/**
 * Read-side Product query.
 *
 * Pilot 1 (`item`) shipped the single-row lookup keyed by productCode.
 * Wave 8 (admin product management) adds list-projection methods used
 * by the admin grid (`listAll`) and the admin filter search
 * (`search`). Both list methods walk the same underlying corpus —
 * the storage layer is responsible for capping the result set; the
 * Semantic\Limit + Semantic\Offset bounds on the input parameters
 * keep tampered queries from reaching the storage layer with
 * unbounded values.
 *
 * Note: `listAll` returns ALL statuses including STATUS_WITHDRAWN so
 * the admin grid can show soft-deleted rows when the operator wants
 * to audit / restore them. The customer-side product list (when it
 * lands) will use a `listVisible(...)` projection or filter on
 * `productStatus === STATUS_VISIBLE` at that call site — out of
 * scope for Wave 8.
 */
interface ProductQueryInterface
{
    public function item(string $productCode): ProductEntity|null;

    /**
     * Wave 8 (goProductList admin) — paginated list of ALL products
     * (every status). The caller supplies the page bounds; the
     * storage caps internally too. Returns an empty list when the
     * offset is past the corpus end.
     *
     * @return list<ProductEntity>
     */
    public function listAll(int $limit, int $offset = 0): array;

    /**
     * Wave 8 (goProductList admin) — substring filter scan keyed on
     * the product name (matches `productName` only — search across
     * `searchWord` and `description` is a Phase 2 extension). When
     * `$nameKeyword` is null, behaves like `listAll($limit, 0)` for
     * convenience so the resource layer can use a single call. The
     * scan walks all statuses (admin sees everything).
     *
     * @return list<ProductEntity>
     */
    public function search(?string $nameKeyword, int $limit = 50): array;

    /**
     * Wave 8 (goExportProduct admin) — full unpaged dump for the CSV
     * exporter. Walks every product regardless of status. The list
     * is intentionally unbounded here because the export endpoint is
     * admin-only and the Phase 1 fixture is small; Phase 2 will swap
     * this to a streaming cursor.
     *
     * @return list<ProductEntity>
     */
    public function listForExport(): array;
}
