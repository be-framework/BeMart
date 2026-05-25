<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductClassEntity;
use Override;
use PDO;

/**
 * Real PDO-backed ProductClass query — Phase 2b.
 *
 * Mirrors {@see FakeProductClassQuery} against the live EC-CUBE 4.3
 * schema (`dtb_product_class` + joins). Pure prepared statements: no
 * Doctrine, no ORM.
 *
 * The "default product_class" convention
 * --------------------------------------
 * `dtb_product_class` is the per-variation SKU row — one row per
 * `[class_category_id1, class_category_id2]` pair (the two independent
 * variation axes, each a nullable FK to `dtb_class_category`). A product
 * with NO variations carries exactly one row whose two `class_category_id*`
 * columns are both NULL — the "default class". {@see SqlFavoriteStorage}
 * and {@see SqlCartCommand} already pin product-code resolution to that
 * `class_category_id1 IS NULL AND class_category_id2 IS NULL` filter
 * (commits 051d235 / 0757f26); this query stays consistent.
 *
 * {@see ProductClassQueryInterface::item} is keyed by the customer-facing
 * `productCode`. `product_code` lives on `dtb_product_class` directly, so
 * the lookup is a straight `WHERE pc.product_code = :code` — no surrogate-id
 * indirection. The default-class filter then collapses a variation product
 * to its single representative row: a product page would be needed to pick
 * a specific [axis1, axis2] SKU, and the cart-add / reorder Finals that
 * consume this query have no such concept. A productCode that only ever
 * appears on a variation row (no default class) is an honest miss → null,
 * exactly the Fake's "key absent" behaviour.
 *
 * JOINs
 * -----
 *   - `dtb_product` (INNER) — `ProductClassEntity::productName` is the
 *     product header name (`dtb_product.name`); it does NOT live on the
 *     class row. Same id→header JOIN shape as {@see SqlFavoriteStorage}.
 *     A class row whose `product_id` does not resolve is dropped by the
 *     INNER JOIN (FK breakage → miss).
 *   - `mtb_sale_type` (LEFT) — `saleTypeName` is the human-readable label.
 *     `sale_type_id` is a NULLABLE FK; the LEFT JOIN keeps the class row
 *     when it carries NULL (saleTypeName coalesces to '', saleTypeId to
 *     0). A NON-NULL `sale_type_id` always resolves a master row — the FK
 *     constraint FK_1A11D1BAB0524E01 is enforced, so unlike
 *     {@see SqlCartQuery::lookupSaleTypeName} (where saleTypeId is
 *     derived from the cart_key suffix, not an FK) the empty-master
 *     fallback here only fires for a genuinely NULL sale_type_id.
 *     `saleTypeId` is read raw off `dtb_product_class.sale_type_id`.
 *
 * Column ↔ field coercions (hydrate)
 * ----------------------------------
 *   - `stock` — `decimal(10,0)` nullable. The Entity types it `int|null`;
 *     NULL stays NULL (unlimited-stock products), a value casts to int.
 *     EC-CUBE keeps the live count in the 1:1 `dtb_product_stock` table
 *     too, but `dtb_product_class.stock` is the denormalised mirror the
 *     Favorite/Cart slice (and `insertProduct`) already write and read —
 *     this query stays on that column for consistency rather than adding
 *     a `dtb_product_stock` JOIN the Entity does not need.
 *   - `stock_unlimited` — `tinyint(1)` NOT NULL → bool cast.
 *   - `sale_limit` — `decimal(10,0)` unsigned nullable → `int|null`.
 *   - `price02` — `decimal(12,2)` NOT NULL. JPY money → `(int)` cast
 *     (drops the always-`.00` minor unit, same as the rest of the slice).
 *   - `delivery_fee` — `decimal(12,2)` unsigned nullable; the Entity
 *     types it non-null `int` → NULL coalesces to 0, value casts to int.
 *
 * DI is intentionally NOT wired in production (FakeProductClassQuery
 * remains the bound implementation). The SQL impl is exercised via the
 * test-only override in AbstractResourceSqlTestCase.
 */
final class SqlProductClassQuery implements ProductClassQueryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function item(string $productCode): ProductClassEntity|null
    {
        // INNER JOIN dtb_product for the header name; LEFT JOIN
        // mtb_sale_type so an empty master (structure-only dump) still
        // yields the row. The default-class filter collapses a variation
        // product to its single representative SKU.
        $sql = 'SELECT pc.product_code, p.name AS product_name, '
            . 'pc.stock, pc.stock_unlimited, pc.sale_limit, '
            . 'pc.price02, pc.delivery_fee, '
            . 'pc.sale_type_id, st.name AS sale_type_name '
            . 'FROM dtb_product_class pc '
            . 'INNER JOIN dtb_product p ON p.id = pc.product_id '
            . 'LEFT JOIN mtb_sale_type st ON st.id = pc.sale_type_id '
            . 'WHERE pc.product_code = :product_code '
            . 'AND pc.class_category_id1 IS NULL '
            . 'AND pc.class_category_id2 IS NULL '
            . 'ORDER BY pc.id ASC LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':product_code' => $productCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    /** @param array<string, mixed> $row dtb_product_class columns + joined name fields. */
    private function hydrate(array $row): ProductClassEntity
    {
        return new ProductClassEntity(
            productCode: (string) $row['product_code'],
            productName: (string) $row['product_name'],
            stock: $row['stock'] === null ? null : (int) $row['stock'],
            stockUnlimited: (bool) $row['stock_unlimited'],
            saleLimit: $row['sale_limit'] === null ? null : (int) $row['sale_limit'],
            price02: (int) $row['price02'],
            deliveryFee: $row['delivery_fee'] === null ? 0 : (int) $row['delivery_fee'],
            saleTypeName: $row['sale_type_name'] === null ? '' : (string) $row['sale_type_name'],
            saleTypeId: $row['sale_type_id'] === null ? 0 : (int) $row['sale_type_id'],
        );
    }
}
