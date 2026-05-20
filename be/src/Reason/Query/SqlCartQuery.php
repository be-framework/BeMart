<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use Override;
use PDO;

use function strrpos;
use function substr;

/**
 * Real PDO-backed Cart query — Phase 2a Step 4.
 *
 * Mirrors {@see FakeCartQuery} against the live EC-CUBE 4.3 schema
 * (`dtb_cart` for the header, `dtb_cart_item` for line items). Pure
 * prepared statements — no Doctrine.
 *
 * Mapping notes (see `sql/diff/entity-vs-eccube.md` §CartEntity /
 * §CartItemEntity):
 * - `dtb_cart` has no `sale_type_id` column — saleTypeId is derived
 *   from the `cart_key` suffix (`{sessionPrefix}_{saleTypeId}`). We
 *   split on the LAST underscore so session prefixes that themselves
 *   contain underscores (e.g. `sess_abc_2`) parse correctly.
 * - `saleTypeName` is resolved via a lookup against `mtb_sale_type`.
 *   The structure-only schema dump leaves the master table empty, so
 *   the lookup will normally return null in tests — we default to the
 *   empty string in that case (matching the diff report's "best
 *   effort" stance).
 * - `dtb_cart_item.product_class_id` references `dtb_product_class.id`;
 *   BeMart's `CartItemEntity::productCode` is on `dtb_product_class`,
 *   so the item hydration JOINs cart_item → product_class. The cart
 *   row also carries the display fields EC-CUBE's `ec-cartRow` renders
 *   (product name, thumbnail, detail link, variation axes); those are
 *   resolved by joining onward to dtb_product / dtb_product_image /
 *   dtb_class_category — see {@see fetchItems}.
 * - Money columns (`total_price`, `delivery_fee_total`, `price`) are
 *   `decimal(12,2)` in the schema. We cast to int (JPY assumption —
 *   same as Step 3).
 *
 * `bySessionPrefix` sorts by parsed saleTypeId ascending so test
 * output is stable independent of insert order.
 *
 * DI is intentionally NOT wired in Phase 2a; FakeCartQuery remains
 * the bound implementation.
 */
final class SqlCartQuery implements CartQueryInterface
{
    private const SELECT_CART_COLUMNS = 'id, cart_key, pre_order_id, total_price, delivery_fee_total';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function byCartKey(string $cartKey): CartEntity|null
    {
        $sql = 'SELECT ' . self::SELECT_CART_COLUMNS . ' FROM dtb_cart '
            . 'WHERE cart_key = :cart_key LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cart_key' => $cartKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        return $this->hydrateCart($row);
    }

    /** @return list<CartEntity> */
    #[Override]
    public function bySessionPrefix(string $sessionPrefix): array
    {
        // Escape the LIKE wildcards inside the user-influenced prefix
        // so a `_` or `%` in the session id can't broaden the scan.
        $pattern = $this->escapeLike($sessionPrefix) . '\\_%';

        $sql = 'SELECT ' . self::SELECT_CART_COLUMNS . ' FROM dtb_cart '
            . 'WHERE cart_key LIKE :pattern';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pattern' => $pattern]);

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrateCart($row);
        }

        // Stable ordering for tests: ascending saleTypeId (parsed from
        // the cart_key suffix).
        usort(
            $out,
            static fn (CartEntity $a, CartEntity $b) => $a->saleTypeId <=> $b->saleTypeId,
        );

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateCart(array $row): CartEntity
    {
        $cartKey = (string) $row['cart_key'];
        $saleTypeId = $this->parseSaleTypeId($cartKey);

        return new CartEntity(
            cartKey: $cartKey,
            saleTypeId: $saleTypeId,
            saleTypeName: $this->lookupSaleTypeName($saleTypeId),
            items: $this->fetchItems((int) $row['id']),
            totalPrice: (int) $row['total_price'],
            deliveryFeeTotal: (int) $row['delivery_fee_total'],
            preOrderId: (string) ($row['pre_order_id'] ?? ''),
        );
    }

    /**
     * Extract `saleTypeId` from a `{sessionPrefix}_{saleTypeId}` cart_key.
     * Splits on the LAST underscore so prefixes containing underscores
     * (`sess_abc_2`) still resolve correctly. Returns 0 when the suffix
     * is not numeric — an explicit sentinel rather than a silent miss.
     */
    private function parseSaleTypeId(string $cartKey): int
    {
        $position = strrpos($cartKey, '_');
        if ($position === false) {
            return 0;
        }

        $suffix = substr($cartKey, $position + 1);

        return ctype_digit($suffix) ? (int) $suffix : 0;
    }

    /**
     * Look up the human-readable saleType name. mtb_sale_type is empty
     * in the structure-only test schema, so we default to '' when no
     * row matches — the integration smoke covers the populated path
     * by seeding the master table inline.
     */
    private function lookupSaleTypeName(int $saleTypeId): string
    {
        $stmt = $this->pdo->prepare('SELECT name FROM mtb_sale_type WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $saleTypeId]);
        $name = $stmt->fetchColumn();

        return $name === false ? '' : (string) $name;
    }

    /**
     * Hydrate the items for a single cart. Item ordering is by
     * dtb_cart_item.id ascending — stable for tests and matches the
     * insertion order in the absence of explicit reordering.
     *
     * The JOIN resolves the cart row's display fields against the
     * item's ACTUAL `product_class_id` (the specific purchased SKU,
     * NOT the default class): dtb_product_class → dtb_product (name +
     * id), and dtb_product_class.class_category_id1/2 →
     * dtb_class_category (variation value) → dtb_class_name (axis
     * name). The main image is a correlated sub-select over
     * dtb_product_image for the lowest sort_no. Every join past
     * dtb_product_class is a LEFT JOIN — a product with no image or no
     * variation simply yields NULL, which CartItemEntity's nullable
     * display fields accept.
     *
     * dtb_cart_item rows whose product_class is missing (FK breakage)
     * are skipped silently — they can't render and the safer default
     * is to drop them than throw.
     *
     * @return list<CartItemEntity>
     */
    private function fetchItems(int $cartId): array
    {
        $sql = 'SELECT pc.id AS product_class_id, p.id AS product_id, '
            . 'pc.product_code, p.name AS product_name, ci.quantity, ci.price, '
            . '(SELECT pi.file_name FROM dtb_product_image pi '
            . 'WHERE pi.product_id = p.id '
            . 'ORDER BY pi.sort_no ASC, pi.id ASC LIMIT 1) AS main_image, '
            . 'cc1.name AS class_category_name1, cn1.name AS class_name1, '
            . 'cc2.name AS class_category_name2, cn2.name AS class_name2 '
            . 'FROM dtb_cart_item ci '
            . 'INNER JOIN dtb_product_class pc ON pc.id = ci.product_class_id '
            . 'INNER JOIN dtb_product p ON p.id = pc.product_id '
            . 'LEFT JOIN dtb_class_category cc1 ON cc1.id = pc.class_category_id1 '
            . 'LEFT JOIN dtb_class_name cn1 ON cn1.id = cc1.class_name_id '
            . 'LEFT JOIN dtb_class_category cc2 ON cc2.id = pc.class_category_id2 '
            . 'LEFT JOIN dtb_class_name cn2 ON cn2.id = cc2.class_name_id '
            . 'WHERE ci.cart_id = :cart_id '
            . 'ORDER BY ci.id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cart_id' => $cartId]);

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = new CartItemEntity(
                productCode: (string) ($row['product_code'] ?? ''),
                quantity: (int) $row['quantity'],
                price: (int) $row['price'],
                productClassId: (int) $row['product_class_id'],
                productId: (int) $row['product_id'],
                productName: (string) ($row['product_name'] ?? ''),
                mainImage: $row['main_image'] !== null ? (string) $row['main_image'] : null,
                classCategoryName1: $row['class_category_name1'] !== null ? (string) $row['class_category_name1'] : null,
                className1: $row['class_name1'] !== null ? (string) $row['class_name1'] : null,
                classCategoryName2: $row['class_category_name2'] !== null ? (string) $row['class_category_name2'] : null,
                className2: $row['class_name2'] !== null ? (string) $row['class_name2'] : null,
            );
        }

        return $out;
    }

    /**
     * Escape `%` and `_` so substring keywords can't smuggle wildcards.
     * Uses `\` as the escape character (MySQL default for LIKE).
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
