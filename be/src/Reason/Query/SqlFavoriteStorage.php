<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\FavoriteEntity;
use Override;
use PDO;

/**
 * Real PDO-backed Favorite storage — Phase 2a Step 3.
 *
 * Mirrors {@see FakeFavoriteStorage} against the live EC-CUBE 4.3 schema.
 * Per `sql/diff/entity-vs-eccube.md` §FavoriteEntity this is a Grade B
 * mapping: the row lives in `dtb_customer_favorite_product` (keyed by
 * `product_id`, not `product_code`), so every read joins
 * `dtb_product` (for name + the id→code translation via `dtb_product_class`)
 * and every write resolves `product_code → product_id` up front.
 *
 * Pricing — the "default product_class" convention:
 *   dtb_product_class rows for a product with no variations carry
 *   `class_category_id1 IS NULL AND class_category_id2 IS NULL`. That
 *   is the row we read `price02` (the sell price) and `product_code`
 *   from. Products with class variations have multiple product_class
 *   rows (one per [class_category_id1, class_category_id2] pair);
 *   picking a representative price would need a product-page concept
 *   that goCustomer doesn't have, so we restrict to the default class.
 *   When a product has no default class, the favorite is silently
 *   filtered out — matches EC-CUBE's behavior (the favorites screen
 *   wouldn't be able to render a price-less product anyway).
 *
 * Idempotency — `add()` uses INSERT … ON DUPLICATE KEY UPDATE id=id so
 * the SQL is a no-op when (customer_id, product_id) already exists.
 * 4.3 has no UNIQUE index on the pair (the diff report flags this for
 * a Phase 2b migration), so the no-op behavior is best-effort — a
 * concurrent racing add can still insert a second row. Tests treat the
 * first row as canonical.
 *
 * DI is intentionally NOT wired in Phase 2a; FakeFavoriteStorage remains
 * the bound implementation.
 */
final class SqlFavoriteStorage implements FavoriteStorageInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function add(FavoriteEntity $favorite): void
    {
        $productId = $this->resolveProductId($favorite->productCode);
        if ($productId === null) {
            // Unknown product — drop silently. Phase 2b will surface
            // this as a domain error once the upstream Input validates.
            return;
        }

        if (! ctype_digit($favorite->customerId)) {
            return;
        }

        $sql = 'INSERT INTO dtb_customer_favorite_product '
            . '(customer_id, product_id, create_date, update_date, discriminator_type) '
            . 'VALUES (:customer_id, :product_id, NOW(), NOW(), :discriminator) '
            . 'ON DUPLICATE KEY UPDATE id = id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':customer_id' => (int) $favorite->customerId,
            ':product_id' => $productId,
            ':discriminator' => 'customerfavoriteproduct',
        ]);
    }

    #[Override]
    public function has(string $customerId, string $productCode): bool
    {
        if (! ctype_digit($customerId)) {
            return false;
        }

        $sql = 'SELECT 1 FROM dtb_customer_favorite_product fav '
            . 'INNER JOIN dtb_product_class pc ON pc.product_id = fav.product_id '
            . 'WHERE fav.customer_id = :customer_id '
            . 'AND pc.product_code = :product_code '
            . 'AND pc.class_category_id1 IS NULL '
            . 'AND pc.class_category_id2 IS NULL '
            . 'LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':customer_id' => (int) $customerId,
            ':product_code' => $productCode,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    /** @return list<FavoriteEntity> */
    #[Override]
    public function listByCustomer(string $customerId): array
    {
        if (! ctype_digit($customerId)) {
            return [];
        }

        // Three-way join: favorite → product (for name) → product_class
        // (for code + price). The default-class filter on product_class
        // ensures exactly one row per favorited product.
        $sql = 'SELECT fav.customer_id, pc.product_code, p.name AS product_name, '
            . 'pc.price02 AS unit_price '
            . 'FROM dtb_customer_favorite_product fav '
            . 'INNER JOIN dtb_product p ON p.id = fav.product_id '
            . 'INNER JOIN dtb_product_class pc ON pc.product_id = p.id '
            . 'WHERE fav.customer_id = :customer_id '
            . 'AND pc.class_category_id1 IS NULL '
            . 'AND pc.class_category_id2 IS NULL '
            . 'ORDER BY fav.id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':customer_id' => (int) $customerId]);

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = new FavoriteEntity(
                customerId: (string) $row['customer_id'],
                productCode: (string) ($row['product_code'] ?? ''),
                productName: (string) $row['product_name'],
                unitPrice: (int) $row['unit_price'],
            );
        }

        return $out;
    }

    #[Override]
    public function remove(string $customerId, string $productCode): void
    {
        if (! ctype_digit($customerId)) {
            return;
        }

        $productId = $this->resolveProductId($productCode);
        if ($productId === null) {
            return;
        }

        $sql = 'DELETE FROM dtb_customer_favorite_product '
            . 'WHERE customer_id = :customer_id AND product_id = :product_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':customer_id' => (int) $customerId,
            ':product_id' => $productId,
        ]);
    }

    /**
     * Resolve a BeMart `productCode` (held on `dtb_product_class`) to
     * the surrogate `dtb_product.id` used by the favorite FK. Returns
     * null for unknown codes.
     */
    private function resolveProductId(string $productCode): int|null
    {
        $sql = 'SELECT product_id FROM dtb_product_class '
            . 'WHERE product_code = :product_code '
            . 'AND class_category_id1 IS NULL '
            . 'AND class_category_id2 IS NULL '
            . 'LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':product_code' => $productCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : (int) $row['product_id'];
    }
}
