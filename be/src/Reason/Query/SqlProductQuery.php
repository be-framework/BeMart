<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use Override;
use PDO;
use PDOStatement;

use function str_replace;

/**
 * Real PDO-backed Product query — Phase 2b.
 *
 * Mirrors {@see FakeProductQuery} / {@see FakeProductStorage} against
 * the live EC-CUBE 4.3 schema. Pure prepared statements: no Doctrine,
 * no ORM.
 *
 * The "flattened Product × default ProductClass" shape
 * ----------------------------------------------------
 * {@see ProductEntity} is NOT a 1:1 mirror of one table — it is a
 * flattened row joining `dtb_product` (the catalog header) with the
 * DEFAULT `dtb_product_class` row (the per-variation SKU). The split:
 *
 *   - `productName`   → dtb_product.name
 *   - `productStatus` → dtb_product.product_status_id
 *   - `description`   → dtb_product.description_detail
 *   - `searchWord`    → dtb_product.search_word
 *   - `note`          → dtb_product.note
 *   - `productCode`   → dtb_product_class.product_code
 *   - `price02`       → dtb_product_class.price02
 *   - `stock`         → dtb_product_class.stock
 *
 * `product_code` does NOT exist on `dtb_product` — it lives on
 * `dtb_product_class`. So the natural key the BeMart slice uses
 * (`productCode`, a caller-supplied string) is resolved through the
 * class table. The "default class" is the row whose two
 * `class_category_id*` axes are both NULL — EC-CUBE's convention for a
 * product with no variations. This is the SAME filter
 * {@see SqlProductClassQuery} (commit 19dbd0d), {@see SqlFavoriteStorage}
 * and {@see SqlCartCommand} pin product-code resolution to; this query
 * stays consistent.
 *
 * A productCode that ONLY appears on a non-default variation row never
 * resolves — an honest miss → null, exactly the Fake's "key absent"
 * shape.
 *
 * JOIN
 * ----
 *   - `dtb_product` (INNER) — the header row. A class row whose
 *     `product_id` does not resolve is dropped by the INNER JOIN (FK
 *     breakage → miss). Same id→header JOIN shape as
 *     {@see SqlProductClassQuery} / {@see SqlFavoriteStorage}.
 *
 * Column ↔ field coercions (hydrate)
 * ----------------------------------
 *   - `price02` — `decimal(12,2)` NOT NULL → `(int)` cast (JPY money,
 *     drops the always-`.00` minor unit, same as the rest of the slice).
 *   - `stock` — `decimal(10,0)` nullable → `int|null`; NULL stays NULL
 *     (unlimited-stock products).
 *   - `product_status_id` — `smallint unsigned` nullable FK to the
 *     empty `mtb_product_status` master. ProductEntity::productStatus
 *     is non-null `int` — NULL coalesces to STATUS_VISIBLE (1), the
 *     same default the Fake loader applies for a fixture row that omits
 *     `productStatus`.
 *   - `name` — `varchar(255)` NOT NULL → plain string.
 *   - `note` / `description_detail` / `search_word` — nullable
 *     `longtext` → `string|null`, NULL preserved.
 *
 * DI is intentionally NOT wired in production (FakeProductQuery remains
 * the bound implementation). The SQL impl is exercised via the
 * test-only override in AbstractResourceSqlTestCase.
 */
final class SqlProductQuery implements ProductQueryInterface
{
    /**
     * The flattened Product × default-ProductClass projection. Every
     * read method ({@see item} / {@see listAll} / {@see search} /
     * {@see listForExport}) hydrates from this exact column list, so
     * the SELECT body lives in one place.
     */
    private const SELECT_COLUMNS =
        'pc.product_code, p.name AS product_name, '
        . 'pc.price02, pc.stock, '
        . 'p.product_status_id, p.description_detail, '
        . 'p.search_word, p.note';

    private const FROM_DEFAULT_CLASS =
        'FROM dtb_product_class pc '
        . 'INNER JOIN dtb_product p ON p.id = pc.product_id '
        . 'WHERE pc.class_category_id1 IS NULL '
        . 'AND pc.class_category_id2 IS NULL';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function item(string $productCode): ProductEntity|null
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' '
            . self::FROM_DEFAULT_CLASS . ' '
            . 'AND pc.product_code = :product_code '
            . 'ORDER BY pc.id ASC LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':product_code' => $productCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    /**
     * Paginated dump of ALL products (every status). Walks the default
     * class rows in `dtb_product_class.id` order — the contract test
     * asserts count / presence, not order, same parity convention as
     * the rest of the SQL slice.
     *
     * @return list<ProductEntity>
     */
    #[Override]
    public function listAll(int $limit, int $offset = 0): array
    {
        // LIMIT / OFFSET are bound as integers — the Semantic\Limit +
        // Semantic\Offset bounds on the Input keep tampered values from
        // ever reaching here, but the explicit (int) cast keeps the
        // query well-formed regardless.
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' '
            . self::FROM_DEFAULT_CLASS . ' '
            . 'ORDER BY pc.id ASC '
            . 'LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $this->hydrateAll($stmt);
    }

    /**
     * Substring filter scan on the product name. A null/empty keyword
     * behaves like `listAll($limit, 0)` so the resource layer can use a
     * single call. Scans all statuses (admin sees everything).
     *
     * @return list<ProductEntity>
     */
    #[Override]
    public function search(?string $nameKeyword, int $limit = 50): array
    {
        if ($nameKeyword === null || $nameKeyword === '') {
            return $this->listAll($limit, 0);
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' '
            . self::FROM_DEFAULT_CLASS . ' '
            . 'AND p.name LIKE :keyword '
            . 'ORDER BY pc.id ASC '
            . 'LIMIT :limit';
        $stmt = $this->pdo->prepare($sql);
        // Escape the LIKE metacharacters in the user keyword so a `%`
        // or `_` in the search box matches literally — the keyword is a
        // taint-sourced Input.
        $escaped = $this->escapeLike($nameKeyword);
        $stmt->bindValue(':keyword', '%' . $escaped . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $this->hydrateAll($stmt);
    }

    /**
     * Full unpaged dump for the CSV exporter. Walks every product
     * regardless of status.
     *
     * @return list<ProductEntity>
     */
    #[Override]
    public function listForExport(): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' '
            . self::FROM_DEFAULT_CLASS . ' '
            . 'ORDER BY pc.id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $this->hydrateAll($stmt);
    }

    /**
     * Escape `%`, `_` and the escape char itself so a user keyword is
     * matched literally inside the `LIKE '%...%'` wrapper.
     */
    private function escapeLike(string $keyword): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $keyword,
        );
    }

    /**
     * Drain an executed statement into a hydrated entity list. The
     * row-at-a-time `fetch()` loop keeps Psalm's array shape narrow
     * (each row is `array<string, mixed>`) — same pattern as
     * {@see SqlDeliveryStorage::list}.
     *
     * @return list<ProductEntity>
     */
    private function hydrateAll(PDOStatement $stmt): array
    {
        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrate($row);
        }

        return $out;
    }

    /** @param array<string, mixed> $row dtb_product_class + joined dtb_product columns. */
    private function hydrate(array $row): ProductEntity
    {
        return new ProductEntity(
            productCode: (string) $row['product_code'],
            productName: (string) $row['product_name'],
            price02: (int) $row['price02'],
            stock: $row['stock'] === null ? null : (int) $row['stock'],
            // product_status_id is a nullable FK to the empty
            // mtb_product_status master — NULL coalesces to
            // STATUS_VISIBLE (1), the Fake loader's default.
            productStatus: $row['product_status_id'] === null
                ? ProductEntity::STATUS_VISIBLE
                : (int) $row['product_status_id'],
            description: $row['description_detail'] === null
                ? null
                : (string) $row['description_detail'],
            searchWord: $row['search_word'] === null
                ? null
                : (string) $row['search_word'],
            note: $row['note'] === null ? null : (string) $row['note'],
        );
    }
}
