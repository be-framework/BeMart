<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use PDO;
use RuntimeException;

use function array_keys;
use function array_map;
use function array_merge;
use function bin2hex;
use function date;
use function implode;
use function random_bytes;
use function random_int;
use function sprintf;

/**
 * Shared fixture helpers for the SQL test suite. Used by:
 *
 *  - {@see AbstractSqlTestCase} — storage-layer unit + Final-direct
 *    integration tests under `be/tests/Sql/`.
 *  - `tests\Resource\Sql\AbstractResourceSqlTestCase` — Resource-layer
 *    hypermedia tests under `tests/Resource/Sql/` that drive Final
 *    behavior through `ResourceInterface::get(...)` end-to-end.
 *
 * Both consumers require:
 *   - a live `$this->pdo` (PDO with `PDO::ATTR_ERRMODE = EXCEPTION`)
 *   - per-test transaction discipline (the consumer is responsible for
 *     begin/rollback; the trait only inserts).
 *
 * Helpers default every NOT NULL column to a sensible value so callers
 * only override the fields that matter for their assertion.
 *
 * History: extracted from AbstractSqlTestCase in Phase 2a Step 5 to
 * avoid duplicating ~250 lines across two base classes. AbstractSqlTestCase
 * itself just `use`s this trait — no behavior change.
 */
trait SqlFixturesTrait
{
    /**
     * Insert a dtb_customer row with sensible defaults for every
     * NOT NULL column. Returns the inserted id.
     *
     * Required columns we MUST satisfy on every insert: name01, name02,
     * email, password, secret_key (NOT NULL UNIQUE), point, create_date,
     * update_date, discriminator_type.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertCustomer(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'customer_status_id' => null,
            'sex_id' => null,
            'job_id' => null,
            'country_id' => null,
            'pref_id' => null,
            'name01' => 'Yamada',
            'name02' => 'Taro',
            'kana01' => null,
            'kana02' => null,
            'company_name' => null,
            'postal_code' => null,
            'addr01' => null,
            'addr02' => null,
            'email' => sprintf('test-%d-%d@example.com', $counter, random_int(1000, 9999)),
            'phone_number' => null,
            'birth' => null,
            'password' => 'hashed-password',
            'salt' => null,
            'secret_key' => bin2hex(random_bytes(16)) . '-' . $counter,
            'first_buy_date' => null,
            'last_buy_date' => null,
            'buy_times' => 0,
            'buy_total' => 0.0,
            'note' => null,
            'reset_key' => null,
            'reset_expire' => null,
            'point' => 0,
            'create_date' => $now,
            'update_date' => $now,
            'discriminator_type' => 'customer',
        ], $overrides);

        $this->executeInsert('dtb_customer', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert a dtb_product row plus the default dtb_product_class row
     * (the one with `class_category_id1`/`class_category_id2` both NULL
     * — EC-CUBE's convention for products without variations). Returns
     * the new dtb_product.id.
     *
     * The optional `sale_type_id` / `sale_limit` / `delivery_fee`
     * overrides feed {@see \MyVendor\BeMart\Be\Reason\Query\SqlProductClassQuery},
     * which reads them off the default class row. `sale_type_id` is a
     * nullable FK to the EMPTY mtb_sale_type master — callers that want
     * a non-NULL value (so SqlProductClassQuery resolves a saleTypeName)
     * must seed the master first via {@see seedSaleTypes}.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     *   Recognised keys: `name`, `product_code`, `price02`, `stock`,
     *   `stock_unlimited`, `sale_type_id`, `sale_limit`, `delivery_fee`.
     */
    protected function insertProduct(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');

        $productName = $overrides['name'] ?? sprintf('Test Product %d', $counter);
        $stmt = $this->pdo->prepare(
            'INSERT INTO dtb_product '
            . '(name, create_date, update_date, discriminator_type) '
            . 'VALUES (:name, :created, :updated, :discriminator)',
        );
        $stmt->execute([
            ':name' => $productName,
            ':created' => $now,
            ':updated' => $now,
            ':discriminator' => 'product',
        ]);
        $productId = (int) $this->pdo->lastInsertId();

        $productCode = $overrides['product_code']
            ?? sprintf('TEST-%d-%d', $counter, random_int(1000, 9999));
        $stmt = $this->pdo->prepare(
            'INSERT INTO dtb_product_class '
            . '(product_id, product_code, sale_type_id, sale_limit, '
            . 'delivery_fee, price02, stock, stock_unlimited, '
            . 'visible, create_date, update_date, discriminator_type) '
            . 'VALUES (:product_id, :product_code, :sale_type_id, :sale_limit, '
            . ':delivery_fee, :price02, :stock, '
            . ':stock_unlimited, 1, :created, :updated, :discriminator)',
        );
        $stmt->execute([
            ':product_id' => $productId,
            ':product_code' => $productCode,
            ':sale_type_id' => $overrides['sale_type_id'] ?? null,
            ':sale_limit' => $overrides['sale_limit'] ?? null,
            ':delivery_fee' => $overrides['delivery_fee'] ?? null,
            ':price02' => $overrides['price02'] ?? 1000,
            ':stock' => $overrides['stock'] ?? null,
            ':stock_unlimited' => $overrides['stock_unlimited'] ?? 1,
            ':created' => $now,
            ':updated' => $now,
            ':discriminator' => 'productclass',
        ]);

        return $productId;
    }

    /**
     * Insert an extra dtb_product_class row for an EXISTING product —
     * a per-variation SKU row pinned to a `[class_category_id1,
     * class_category_id2]` axis pair. Returns the new
     * dtb_product_class.id.
     *
     * `insertProduct` already creates the default class (both axes
     * NULL); this helper adds the variation rows that exercise the
     * default-class filter in {@see \MyVendor\BeMart\Be\Reason\Query\SqlProductClassQuery}
     * — a productCode that ONLY appears on a variation row (non-NULL
     * axis) must NOT resolve, because the query restricts to the
     * default class. Callers pass `class_category_id1` (and optionally
     * `class_category_id2`) as ids minted by {@see insertClassCategory}.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     *   Recognised keys: `product_code`, `class_category_id1`,
     *   `class_category_id2`, `price02`, `stock`, `stock_unlimited`,
     *   `sale_type_id`, `sale_limit`, `delivery_fee`.
     */
    protected function insertProductClassVariation(int $productId, array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'product_id' => $productId,
            'sale_type_id' => null,
            'class_category_id1' => null,
            'class_category_id2' => null,
            'delivery_duration_id' => null,
            'creator_id' => null,
            'product_code' => sprintf('VAR-%d-%d', $counter, random_int(1000, 9999)),
            'stock' => null,
            'stock_unlimited' => 1,
            'sale_limit' => null,
            'price01' => null,
            'price02' => 1000,
            'delivery_fee' => null,
            'visible' => 1,
            'create_date' => $now,
            'update_date' => $now,
            'discriminator_type' => 'productclass',
        ], $overrides);

        $this->executeInsert('dtb_product_class', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert (idempotently) an mtb_sale_type row so the FK from
     * dtb_product_class.sale_type_id → mtb_sale_type.id can be
     * satisfied AND {@see \MyVendor\BeMart\Be\Reason\Query\SqlProductClassQuery}
     * can resolve a human-readable saleTypeName.
     *
     * mtb_sale_type is empty in the structure-only schema dump.
     * EC-CUBE 4.3 ships three rows: 1 = 通常販売, 2 = 予約販売,
     * 3 = ダウンロード販売 — the same saleType set the Fake fixture
     * var/fake/product_classes.json uses. {@see seedSaleTypes} seeds
     * all three.
     */
    protected function insertSaleType(int $id, string $name): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO mtb_sale_type (id, name, sort_no, discriminator_type) '
            . 'VALUES (:id, :name, :sort_no, :discriminator)',
        );
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':sort_no' => $id,
            ':discriminator' => 'saletype',
        ]);
    }

    /**
     * Seed the master table mtb_sale_type (referenced by
     * dtb_product_class.sale_type_id) with the EC-CUBE 4.3 canonical
     * rows. Idempotent (`INSERT IGNORE`), so calling it once in setUp
     * is sufficient — same precedent as {@see seedDeviceTypes} /
     * {@see seedAdminMasters}.
     */
    protected function seedSaleTypes(): void
    {
        $this->insertSaleType(1, '通常販売');
        $this->insertSaleType(2, '予約販売');
        $this->insertSaleType(3, 'ダウンロード販売');
    }

    /**
     * Insert a dtb_order row with sensible defaults for every NOT NULL
     * column. Returns `['id' => int, 'orderNo' => string]`.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertOrder(array $overrides = []): array
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $orderNo = $overrides['order_no']
            ?? sprintf('TEST-ORD-%d-%d', $counter, random_int(10000, 99999));

        $row = array_merge([
            'customer_id' => null,
            'payment_id' => null,
            'order_status_id' => FinalizedOrderEntity::STATUS_NEW,
            'pre_order_id' => sprintf('preorder-%d-%d', $counter, random_int(10000, 99999)),
            'order_no' => $orderNo,
            'name01' => 'Yamada',
            'name02' => 'Taro',
            'subtotal' => 1000,
            'discount' => 0,
            'delivery_fee_total' => 500,
            'charge' => 0,
            'tax' => 100,
            'total' => 1600,
            'payment_total' => 1600,
            'add_point' => 0,
            'use_point' => 0,
            'order_date' => $now,
            'payment_date' => $now,
            'create_date' => $now,
            'update_date' => $now,
            'discriminator_type' => 'order',
        ], $overrides);

        $this->executeInsert('dtb_order', $row);

        return [
            'id' => (int) $this->pdo->lastInsertId(),
            'orderNo' => (string) $row['order_no'],
        ];
    }

    /**
     * Insert a dtb_order_item row. Returns the new id.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertOrderItem(int $orderId, array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $row = array_merge([
            'order_id' => $orderId,
            'product_id' => null,
            'product_class_id' => null,
            'shipping_id' => null,
            'rounding_type_id' => null,
            'tax_type_id' => null,
            'tax_display_type_id' => null,
            'order_item_type_id' => null,
            'product_name' => sprintf('Test Item %d', $counter),
            'product_code' => sprintf('TEST-ITEM-%d', $counter),
            'price' => 1000,
            'quantity' => 1,
            'tax' => 100,
            'tax_rate' => 10,
            'tax_adjust' => 0,
            'discriminator_type' => 'orderitem',
        ], $overrides);

        $this->executeInsert('dtb_order_item', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert a dtb_shipping row (one per-order delivery target).
     * Returns the inserted id.
     *
     * dtb_shipping's NOT NULL columns are name01, name02, create_date,
     * update_date, discriminator_type. Every FK column (order_id →
     * dtb_order, pref_id → mtb_pref, country_id → mtb_country,
     * delivery_id → dtb_delivery, creator_id → dtb_member) plus the
     * remaining address columns are nullable and default to NULL so
     * callers only override what they need. Most call sites pass
     * `order_id` (the FK SqlShippingAddressStorage resolves order_no
     * against) and the address fields under assertion.
     *
     * The defaults match SqlShippingAddressStorage's INSERT contract:
     * pref_id = NULL (mtb_pref is empty in the structure-only dump —
     * callers wanting a non-NULL pref must seed it via {@see insertPref}),
     * discriminator_type = 'shipping'.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertShipping(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'order_id' => null,
            'country_id' => null,
            'pref_id' => null,
            'name01' => 'Yamada',
            'name02' => sprintf('Ship%d', $counter),
            'postal_code' => null,
            'addr01' => null,
            'addr02' => null,
            'phone_number' => null,
            'create_date' => $now,
            'update_date' => $now,
            'discriminator_type' => 'shipping',
        ], $overrides);

        $this->executeInsert('dtb_shipping', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Look up the default `dtb_product_class.id` for a given
     * `dtb_product.id`. Convention: the row with both
     * `class_category_id1` and `class_category_id2` NULL — the same
     * "default class" filter SqlFavoriteStorage and SqlCartCommand
     * use to translate productCode ↔ surrogate ids.
     */
    protected function defaultProductClassId(int $productId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM dtb_product_class '
            . 'WHERE product_id = :product_id '
            . 'AND class_category_id1 IS NULL '
            . 'AND class_category_id2 IS NULL '
            . 'LIMIT 1',
        );
        $stmt->execute([':product_id' => $productId]);
        $id = $stmt->fetchColumn();
        if ($id === false) {
            throw new RuntimeException(sprintf(
                'No default product_class for product_id=%d (helper expects '
                . 'insertProduct() to have created one)',
                $productId,
            ));
        }

        return (int) $id;
    }

    /**
     * Insert a dtb_cart row with sensible defaults. Returns
     * `['id' => int, 'cartKey' => string, 'preOrderId' => string]`.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertCart(array $overrides = []): array
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'customer_id' => null,
            'cart_key' => sprintf('sess-%d-%d_10', $counter, random_int(1000, 9999)),
            'pre_order_id' => sprintf('pre-%d-%d', $counter, random_int(10000, 99999)),
            'total_price' => 0,
            'delivery_fee_total' => 0,
            'sort_no' => null,
            'create_date' => $now,
            'update_date' => $now,
            'add_point' => 0,
            'use_point' => 0,
            'discriminator_type' => 'cart',
        ], $overrides);

        $this->executeInsert('dtb_cart', $row);

        return [
            'id' => (int) $this->pdo->lastInsertId(),
            'cartKey' => (string) $row['cart_key'],
            'preOrderId' => $row['pre_order_id'] === null ? '' : (string) $row['pre_order_id'],
        ];
    }

    /**
     * Insert a dtb_cart_item row. Returns the new id.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertCartItem(int $cartId, int $productClassId, array $overrides = []): int
    {
        $row = array_merge([
            'product_class_id' => $productClassId,
            'cart_id' => $cartId,
            'price' => 1000,
            'quantity' => 1,
            'point_rate' => null,
            'discriminator_type' => 'cartitem',
        ], $overrides);

        $this->executeInsert('dtb_cart_item', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert (idempotent) an mtb_pref row so the FK from
     * dtb_customer_address.pref_id → mtb_pref.id can be satisfied.
     * The structure-only schema dump leaves mtb_pref empty, so any
     * test that wants to set a non-NULL pref must seed the master row
     * first. id is supplied (matches the EC-CUBE 1..47 prefecture
     * convention); name/sort_no get sensible placeholders.
     */
    protected function insertPref(int $id, string $name = 'Tokyo'): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO mtb_pref (id, name, sort_no, discriminator_type) '
            . 'VALUES (:id, :name, :sort_no, :discriminator)',
        );
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':sort_no' => $id,
            ':discriminator' => 'pref',
        ]);
    }

    /**
     * Insert a dtb_customer_address row with sensible defaults. Returns
     * the inserted id.
     *
     * NOT NULL columns we MUST satisfy: name01, name02, create_date,
     * update_date, discriminator_type. Everything else (customer_id,
     * country_id, pref_id, kana01/02, company_name, postal_code,
     * addr01/02, phone_number) is column-nullable and defaults to NULL
     * so callers only override what they need. Most call sites pass
     * `customer_id` (the FK to dtb_customer) since the SqlAddressStorage
     * read paths filter by it.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertAddress(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'customer_id' => null,
            'country_id' => null,
            'pref_id' => null,
            'name01' => 'Yamada',
            'name02' => sprintf('Addr%d', $counter),
            'kana01' => null,
            'kana02' => null,
            'company_name' => null,
            'postal_code' => null,
            'addr01' => null,
            'addr02' => null,
            'phone_number' => null,
            'create_date' => $now,
            'update_date' => $now,
            'discriminator_type' => 'customeraddress',
        ], $overrides);

        $this->executeInsert('dtb_customer_address', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert a dtb_tag row. Returns the inserted id.
     *
     * dtb_tag has only four columns and no FK constraints (id is
     * AUTO_INCREMENT, name / sort_no / discriminator_type are NOT
     * NULL). The defaults match SqlTagStorage's write contract
     * (sort_no = 0, discriminator_type = 'tag') so callers that want
     * to mimic an admin-created tag only need to override `name`.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertTag(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $row = array_merge([
            'name' => sprintf('Tag-%d', $counter),
            'sort_no' => 0,
            'discriminator_type' => 'tag',
        ], $overrides);

        $this->executeInsert('dtb_tag', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert (idempotently) the singleton dtb_base_info row at id=1.
     *
     * dtb_base_info is a single-row config table — EC-CUBE's installer
     * always writes id=1 and every admin screen reads it back the same
     * way. The structure-only schema dump leaves the table empty, so
     * any SQL test that wants {@see SqlBaseInfoStorage} to return a
     * non-default row must seed it first.
     *
     * `INSERT IGNORE` makes the helper a no-op on second call inside
     * the same test — useful when both a hypermedia test's setUp and
     * a per-case override want a row to exist with their own values
     * (the test that needs the override should call the helper once
     * with the overrides, not first the seed then the overrides).
     *
     * NOT NULL columns we MUST satisfy: update_date,
     * option_mypage_order_status_display (DEFAULT 1),
     * option_nostock_hidden (DEFAULT 0), option_favorite_product
     * (DEFAULT 1), option_product_delivery_fee (DEFAULT 0),
     * option_product_tax_rule (DEFAULT 0), option_customer_activate
     * (DEFAULT 1), option_remember_me (DEFAULT 1), option_mail_notifier
     * (DEFAULT 0), option_point (DEFAULT 1), discriminator_type.
     * The schema covers all the option_* tinyints via DEFAULT so we
     * only supply update_date and discriminator_type plus whatever
     * shop-info columns the caller overrides.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     *   Use the schema column names (snake_case: `shop_name`,
     *   `pref_id`, `email01`, `message`, etc.), not the Entity field
     *   names — same convention as the other fixture helpers.
     */
    protected function insertBaseInfo(array $overrides = []): int
    {
        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'id' => 1,
            'country_id' => null,
            'pref_id' => null,
            'company_name' => null,
            'company_kana' => null,
            'postal_code' => null,
            'addr01' => null,
            'addr02' => null,
            'phone_number' => null,
            'business_hour' => null,
            'email01' => null,
            'email02' => null,
            'email03' => null,
            'email04' => null,
            'shop_name' => null,
            'shop_kana' => null,
            'shop_name_eng' => null,
            'update_date' => $now,
            'good_traded' => null,
            'message' => null,
            'delivery_free_amount' => null,
            'delivery_free_quantity' => null,
            'invoice_registration_number' => null,
            'authentication_key' => null,
            'php_path' => null,
            'ga_id' => null,
            'discriminator_type' => 'baseinfo',
        ], $overrides);

        // INSERT IGNORE so a second call inside the same transaction
        // is a silent no-op (singleton row contract).
        $columns = array_keys($row);
        $placeholders = array_map(static fn (string $c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT IGNORE INTO dtb_base_info (%s) VALUES (%s)',
            implode(', ', array_map(static fn (string $c) => '`' . $c . '`', $columns)),
            implode(', ', $placeholders),
        );
        $stmt = $this->pdo->prepare($sql);
        $params = [];
        foreach ($row as $col => $value) {
            $params[':' . $col] = $value;
        }

        $stmt->execute($params);

        return (int) $row['id'];
    }

    /**
     * Insert (idempotently) a dtb_tradelaw carrier row.
     *
     * The 特定商取引法 page is, in EC-CUBE proper, up to 15 per-item
     * rows. The Wave-8 BeMart {@see \MyVendor\BeMart\Be\Reason\Query\TradeLawStorageInterface}
     * is single-blob — {@see \MyVendor\BeMart\Be\Reason\Query\SqlTradeLawStorage}
     * stores the whole-page body in ONE carrier row's `description`
     * column at `dtb_tradelaw.id = 1` (the same singleton-row shape
     * SqlBaseInfoStorage uses for dtb_base_info.id=1). This helper
     * seeds that carrier row so a SQL test can exercise a non-default
     * `get()` projection.
     *
     * dtb_tradelaw's NOT NULL columns are id (AUTO_INCREMENT, supplied
     * explicitly here), sort_no (smallint, no DEFAULT),
     * display_order_screen (tinyint(1), no DEFAULT), discriminator_type.
     * `name` and `description` are nullable. The defaults match
     * SqlTradeLawStorage's INSERT contract: name = NULL (the Wave-8
     * blob has no per-item name), sort_no = 1, display_order_screen = 0,
     * discriminator_type = 'tradelaw'. The structure-only schema dump
     * seeds no rows, so any SQL test that wants SqlTradeLawStorage to
     * return a non-default body must seed it first.
     *
     * `INSERT IGNORE` makes the helper a no-op on a second call inside
     * the same transaction — same singleton-row convention as
     * {@see insertBaseInfo}.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     *   Use the schema column names (snake_case: `description`,
     *   `sort_no`, `display_order_screen`).
     */
    protected function insertTradeLaw(array $overrides = []): int
    {
        $row = array_merge([
            'id' => 1,
            'name' => null,
            'description' => null,
            'sort_no' => 1,
            'display_order_screen' => 0,
            'discriminator_type' => 'tradelaw',
        ], $overrides);

        // INSERT IGNORE so a second call inside the same transaction
        // is a silent no-op (singleton carrier-row contract).
        $columns = array_keys($row);
        $placeholders = array_map(static fn (string $c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT IGNORE INTO dtb_tradelaw (%s) VALUES (%s)',
            implode(', ', array_map(static fn (string $c) => '`' . $c . '`', $columns)),
            implode(', ', $placeholders),
        );
        $stmt = $this->pdo->prepare($sql);
        $params = [];
        foreach ($row as $col => $value) {
            $params[':' . $col] = $value;
        }

        $stmt->execute($params);

        return (int) $row['id'];
    }

    /**
     * Insert a dtb_tax_rule row. Returns the inserted id.
     *
     * dtb_tax_rule's NOT NULL columns are tax_rate (decimal(10,0)),
     * tax_adjust (decimal(10,0) DEFAULT 0), apply_date (datetime),
     * create_date, update_date, discriminator_type. Five FK columns
     * (product_class_id / product_id / country_id / pref_id /
     * creator_id) plus rounding_type_id all default to NULL — the
     * relevant master / parent tables (mtb_country / mtb_pref /
     * mtb_rounding_type / dtb_member / dtb_product / dtb_product_class)
     * are empty in the structure-only dump, so callers that want
     * non-NULL FKs must seed the targets first (same convention as
     * `insertAddress` / `insertBaseInfo`).
     *
     * The defaults match SqlTaxRuleStorage's INSERT contract:
     * rounding_type_id = NULL (re-derived to roundingType=1 on read),
     * tax_adjust = 0, discriminator_type = 'taxrule'.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertTaxRule(array $overrides = []): int
    {
        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'product_class_id' => null,
            'creator_id' => null,
            'country_id' => null,
            'pref_id' => null,
            'product_id' => null,
            'rounding_type_id' => null,
            'tax_rate' => 10,
            'tax_adjust' => 0,
            'apply_date' => $now,
            'create_date' => $now,
            'update_date' => $now,
            'discriminator_type' => 'taxrule',
        ], $overrides);

        $this->executeInsert('dtb_tax_rule', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert a dtb_news row. Returns the inserted id.
     *
     * dtb_news's NOT NULL columns are title (varchar(255)), link_method
     * (tinyint(1) DEFAULT 0), create_date, update_date, visible
     * (tinyint(1) DEFAULT 1), discriminator_type. creator_id (FK to
     * dtb_member) and publish_date are nullable. The defaults match
     * SqlNewsStorage's INSERT contract: creator_id = NULL (dtb_member
     * is empty in the structure-only dump so any non-NULL FK value
     * would raise 1452), visible = 1 (default-shown), discriminator
     * = 'news'.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertNews(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'creator_id' => null,
            'publish_date' => $now,
            'title' => sprintf('News %d', $counter),
            'description' => null,
            'url' => null,
            'link_method' => 0,
            'visible' => 1,
            'create_date' => $now,
            'update_date' => $now,
            'discriminator_type' => 'news',
        ], $overrides);

        $this->executeInsert('dtb_news', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert a dtb_page row. Returns the inserted id.
     *
     * dtb_page's NOT NULL columns are url (varchar(255)), edit_type
     * (smallint DEFAULT 1), create_date, update_date, discriminator_type.
     * Every other column (master_page_id, page_name, file_name, author,
     * description, keyword, meta_robots, meta_tags) is nullable. The
     * defaults match SqlPageStorage's INSERT contract:
     * master_page_id = NULL (the dtb_page self-FK is a Phase-2 layout
     * concern, not exposed by the admin slice), edit_type = 0
     * (EDIT_TYPE_USER — the value PageCreated writes for new free
     * pages), discriminator_type = 'page'. Pass `edit_type` >= 2 to
     * mimic an EC-CUBE system page (PageDeleted refuses deletion in
     * that range, mirroring the Fake-backed `pg-homepage` seed which
     * has pageEditType = 2).
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertPage(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'master_page_id' => null,
            'page_name' => sprintf('Page %d', $counter),
            'url' => sprintf('page_%d', $counter),
            'file_name' => sprintf('page_%d', $counter),
            'edit_type' => 0,
            'author' => null,
            'description' => null,
            'keyword' => null,
            'create_date' => $now,
            'update_date' => $now,
            'meta_robots' => null,
            'meta_tags' => null,
            'discriminator_type' => 'page',
        ], $overrides);

        $this->executeInsert('dtb_page', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert a dtb_block row. Returns the inserted id.
     *
     * dtb_block's NOT NULL columns are file_name (varchar(255)),
     * use_controller (tinyint(1) DEFAULT 0), deletable (tinyint(1)
     * DEFAULT 1), create_date, update_date, discriminator_type.
     * device_type_id (FK to mtb_device_type) and block_name are
     * nullable. The defaults match SqlBlockStorage's INSERT contract:
     * device_type_id = NULL (mtb_device_type is empty in the
     * structure-only dump — any non-NULL value would raise FK 1452),
     * use_controller = 0 (plain template, no controller),
     * deletable = 1 (user-editable — matches the BlockCreated default),
     * discriminator_type = 'block'. Pass `deletable` = 0 to mimic an
     * EC-CUBE system block (BlockDeleted refuses deletion when the row's
     * blockDeletable is false, mirroring the Fake-backed `bk-header`
     * seed which has blockDeletable = false).
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertBlock(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'device_type_id' => null,
            'block_name' => sprintf('Block %d', $counter),
            'file_name' => sprintf('block_%d', $counter),
            'use_controller' => 0,
            'deletable' => 1,
            'create_date' => $now,
            'update_date' => $now,
            'discriminator_type' => 'block',
        ], $overrides);

        $this->executeInsert('dtb_block', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert a dtb_category row. Returns the inserted id.
     *
     * dtb_category's NOT NULL columns are category_name (varchar(255)),
     * hierarchy (int unsigned — the depth cache, no DEFAULT), sort_no
     * (int(11), no DEFAULT), create_date, update_date,
     * discriminator_type. `parent_category_id` is a nullable self-FK
     * (FK_5ED2C2B796A8F92 → dtb_category.id) and `creator_id` a
     * nullable FK to dtb_member.
     *
     * The defaults match SqlCategoryStorage's INSERT contract:
     * parent_category_id = NULL (root category), creator_id = NULL
     * (dtb_member is empty in the structure-only dump so any non-NULL
     * value would raise FK 1452), hierarchy = 1 (root depth),
     * sort_no = 0, discriminator_type = 'category'.
     *
     * Self-FK ordering: a child category MUST be inserted after its
     * parent. Callers seeding a tree pass the parent's id back as the
     * `parent_category_id` override and bump `hierarchy` accordingly
     * (root=1, child=2…) — same depth cache SqlCategoryStorage derives
     * on its own INSERT path.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertCategory(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'parent_category_id' => null,
            'creator_id' => null,
            'category_name' => sprintf('Category %d', $counter),
            'hierarchy' => 1,
            'sort_no' => 0,
            'create_date' => $now,
            'update_date' => $now,
            'discriminator_type' => 'category',
        ], $overrides);

        $this->executeInsert('dtb_category', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert a dtb_class_name row (a product-variation AXIS — e.g.
     * "Color" / "Size"). Returns the inserted id.
     *
     * dtb_class_name's NOT NULL columns are name (varchar(255)),
     * sort_no (int unsigned — display order, no DEFAULT), create_date,
     * update_date, discriminator_type. `backend_name` is a nullable
     * admin-only internal name and `creator_id` a nullable FK to
     * dtb_member.
     *
     * The defaults match SqlClassNameStorage's INSERT contract:
     * backend_name = NULL, creator_id = NULL (dtb_member is empty in
     * the structure-only dump so any non-NULL value would raise FK
     * 1452), sort_no = 0, discriminator_type = 'classname'.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertClassName(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'creator_id' => null,
            'backend_name' => null,
            'name' => sprintf('Axis %d', $counter),
            'sort_no' => 0,
            'create_date' => $now,
            'update_date' => $now,
            'discriminator_type' => 'classname',
        ], $overrides);

        $this->executeInsert('dtb_class_name', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert a dtb_class_category row (an axis VALUE — e.g. "Red"
     * under the "Color" axis). Returns the inserted id.
     *
     * Used only to seed a child row that exercises
     * SqlClassNameStorage::remove's defensive
     * `DELETE FROM dtb_class_category WHERE class_name_id = ?` cascade
     * (FK_9B0D1DBAB462FB2A). dtb_class_category's NOT NULL columns are
     * name, sort_no, visible (DEFAULT 1), create_date, update_date,
     * discriminator_type; class_name_id is the nullable FK back to
     * dtb_class_name.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertClassCategory(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'class_name_id' => null,
            'creator_id' => null,
            'backend_name' => null,
            'name' => sprintf('Value %d', $counter),
            'sort_no' => 0,
            'visible' => 1,
            'create_date' => $now,
            'update_date' => $now,
            'discriminator_type' => 'classcategory',
        ], $overrides);

        $this->executeInsert('dtb_class_category', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert (idempotently) an mtb_device_type row so the FK from
     * dtb_layout.device_type_id → mtb_device_type.id can be satisfied.
     *
     * mtb_device_type is empty in the structure-only schema dump.
     * EC-CUBE 4.3 ships exactly two rows: 10 = PC, 2 = モバイル — the
     * non-contiguous ids are a leftover of the 2.x garake-era device
     * support. Those are the only `deviceType` values LayoutEntity
     * ever carries. {@see seedDeviceTypes} seeds both.
     */
    protected function insertDeviceType(int $id, string $name): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO mtb_device_type (id, name, sort_no, discriminator_type) '
            . 'VALUES (:id, :name, :sort_no, :discriminator)',
        );
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':sort_no' => $id,
            ':discriminator' => 'devicetype',
        ]);
    }

    /**
     * Seed the master table dtb_layout's FK references
     * (mtb_device_type) with the EC-CUBE 4.3 canonical rows.
     *
     * The structure-only schema dump leaves mtb_device_type empty. Any
     * SQL test that inserts a dtb_layout row with a NON-NULL
     * device_type_id MUST call this first. Idempotent (`INSERT
     * IGNORE`), so calling it once in setUp is sufficient.
     */
    protected function seedDeviceTypes(): void
    {
        $this->insertDeviceType(10, 'PC');
        $this->insertDeviceType(2, 'モバイル');
    }

    /**
     * Insert a dtb_layout row. Returns the inserted id.
     *
     * dtb_layout's NOT NULL columns are create_date, update_date,
     * discriminator_type. `layout_name` is a nullable varchar(255) and
     * `device_type_id` a nullable smallint FK to mtb_device_type
     * (FK_5A62AA7C4FFA550E).
     *
     * The default writes `device_type_id = 10` (PC) so the LayoutEntity
     * projection round-trips a real EC-CUBE device enum value — callers
     * MUST seed the master row first via {@see seedDeviceTypes} (or
     * pass `device_type_id` => null to write a NULL, which the
     * SqlLayoutStorage hydrator coalesces back to deviceType = 0).
     * discriminator_type is 'layout' — the Doctrine single-table
     * inheritance value EC-CUBE writes, same as SqlLayoutStorage's
     * INSERT contract.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertLayout(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'device_type_id' => 10,
            'layout_name' => sprintf('Layout %d', $counter),
            'create_date' => $now,
            'update_date' => $now,
            'discriminator_type' => 'layout',
        ], $overrides);

        $this->executeInsert('dtb_layout', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert a dtb_template row. Returns the inserted id.
     *
     * dtb_template's column shape matches dtb_layout: NOT NULL columns
     * are template_code, template_name, create_date, update_date,
     * discriminator_type; `device_type_id` is a nullable smallint FK to
     * mtb_device_type (FK_94C12A694FFA550E).
     *
     * The default writes `device_type_id = 10` (PC) so the
     * TemplateEntity projection round-trips a real EC-CUBE device enum
     * value — callers MUST seed the master row first via
     * {@see seedDeviceTypes} (or pass `device_type_id` => null to write
     * a NULL, which the SqlTemplateStorage hydrator coalesces back to
     * deviceType = 0). template_code is the install-time unique code
     * (the stock EC-CUBE template is 'default'); a per-row counter
     * keeps it unique across calls. discriminator_type is 'template' —
     * the Doctrine single-table inheritance value EC-CUBE writes.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertTemplate(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'device_type_id' => 10,
            'template_code' => sprintf('template-%d', $counter),
            'template_name' => sprintf('Template %d', $counter),
            'create_date' => $now,
            'update_date' => $now,
            'discriminator_type' => 'template',
        ], $overrides);

        $this->executeInsert('dtb_template', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert (idempotently) an mtb_work row so the FK from
     * dtb_member.work_id → mtb_work.id can be satisfied.
     *
     * mtb_work is empty in the structure-only schema dump. EC-CUBE
     * 4.3 ships exactly two rows: 0 = NON_ACTIVE, 1 = ACTIVE — those
     * are the only `work` values AdminEntity / SqlAdminCommand ever
     * write (a fresh admin is ACTIVE; soft-delete flips to
     * NON_ACTIVE). {@see seedAdminMasters} seeds both.
     */
    protected function insertWork(int $id, string $name): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO mtb_work (id, name, sort_no, discriminator_type) '
            . 'VALUES (:id, :name, :sort_no, :discriminator)',
        );
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':sort_no' => $id,
            ':discriminator' => 'work',
        ]);
    }

    /**
     * Insert (idempotently) an mtb_authority row so the FK from
     * dtb_member.authority_id → mtb_authority.id can be satisfied.
     *
     * mtb_authority is empty in the structure-only dump. EC-CUBE 4.3
     * ships 0 = システム管理者 and 1 = 店舗オーナー — the two values the
     * BeMart admin slice uses. {@see seedAdminMasters} seeds both.
     */
    protected function insertAuthority(int $id, string $name): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO mtb_authority (id, name, sort_no, discriminator_type) '
            . 'VALUES (:id, :name, :sort_no, :discriminator)',
        );
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':sort_no' => $id,
            ':discriminator' => 'authority',
        ]);
    }

    /**
     * Seed the two master tables dtb_member's FKs reference
     * (mtb_work + mtb_authority) with the EC-CUBE 4.3 canonical rows.
     *
     * The structure-only schema dump leaves both tables empty. Any
     * SQL test that inserts a dtb_member row with a NON-NULL work_id
     * / authority_id — or that exercises soft-delete (which writes an
     * explicit work_id=0) — MUST call this first. Idempotent
     * (`INSERT IGNORE`), so calling it once in setUp is sufficient.
     */
    protected function seedAdminMasters(): void
    {
        $this->insertWork(0, 'NON_ACTIVE');
        $this->insertWork(1, 'ACTIVE');
        $this->insertAuthority(0, 'システム管理者');
        $this->insertAuthority(1, '店舗オーナー');
    }

    /**
     * Insert a dtb_member row (admin account). Returns the inserted id.
     *
     * dtb_member's NOT NULL columns are login_id, password, sort_no
     * (smallint, no DEFAULT), create_date, update_date,
     * discriminator_type, plus `two_factor_auth_enabled` (tinyint(1)
     * NOT NULL DEFAULT 0). `work_id` / `authority_id` are nullable
     * FKs to mtb_work / mtb_authority — both EMPTY in the structure-
     * only dump. The defaults below write `work_id=1` (ACTIVE) and
     * `authority_id=0` (system admin), so callers MUST seed those
     * master rows first via {@see seedAdminMasters} (or pass `null`
     * overrides). `creator_id` is a self-FK on dtb_member; leaving
     * NULL avoids the chicken-and-egg of the first row.
     *
     * The defaults match {@see \MyVendor\BeMart\Be\Reason\Query\SqlAdminCommand}'s
     * INSERT contract (sort_no = 0, discriminator_type = 'member',
     * password = the bcrypt hash of `admin-test-password-2026`).
     * Same hash the Fake fixture `var/fake/admins.json` uses for
     * `test-admin`, so the admin-login Resource SQL test can attempt
     * a login against a freshly-inserted row with the canonical
     * plaintext.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertAdmin(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'work_id' => 1,
            'authority_id' => 0,
            'creator_id' => null,
            'name' => sprintf('Admin-%d', $counter),
            'department' => null,
            'login_id' => sprintf('admin-%d-%d', $counter, random_int(1000, 9999)),
            // Canonical bcrypt of `admin-test-password-2026` — same
            // value var/fake/admins.json carries for test-admin so the
            // admin-login Resource SQL sibling can verify with the
            // hard-coded plaintext used by the Fake-backed test.
            'password' => '$2y$12$Vl/YKSI0DjUOxYJWH9ytAeVk3Z7l21e.6UM7gh46gpdsbvT4OQ4eG',
            'salt' => null,
            'sort_no' => 0,
            'two_factor_auth_key' => null,
            'two_factor_auth_enabled' => 0,
            'create_date' => $now,
            'update_date' => $now,
            'login_date' => null,
            'discriminator_type' => 'member',
        ], $overrides);

        $this->executeInsert('dtb_member', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert (idempotently) an mtb_login_history_status row so the FK
     * from dtb_login_history.login_history_status_id →
     * mtb_login_history_status.id can be satisfied.
     *
     * mtb_login_history_status is empty in the structure-only schema
     * dump. EC-CUBE 4.3 ships exactly two rows: 0 = 失敗 (FAILURE),
     * 1 = 成功 (SUCCESS) — the only two values
     * {@see \MyVendor\BeMart\Be\Reason\Query\SqlLoginHistoryStorage}
     * ever writes (a successful attempt → 1, a failed attempt → 0).
     * {@see seedLoginHistoryStatus} seeds both.
     */
    protected function insertLoginHistoryStatus(int $id, string $name): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO mtb_login_history_status '
            . '(id, name, sort_no, discriminator_type) '
            . 'VALUES (:id, :name, :sort_no, :discriminator)',
        );
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':sort_no' => $id,
            ':discriminator' => 'loginhistorystatus',
        ]);
    }

    /**
     * Seed the master table dtb_login_history's FK references
     * (mtb_login_history_status) with the EC-CUBE 4.3 canonical rows.
     *
     * The structure-only schema dump leaves mtb_login_history_status
     * empty, and `dtb_login_history.login_history_status_id` is NOT
     * NULL with a non-deferrable FK — so any SQL test that appends a
     * dtb_login_history row MUST call this first. Idempotent (`INSERT
     * IGNORE`), so calling it once in setUp is sufficient. Same
     * precedent {@see seedAdminMasters} set for the analogous
     * empty-master FK case.
     */
    protected function seedLoginHistoryStatus(): void
    {
        $this->insertLoginHistoryStatus(0, '失敗');
        $this->insertLoginHistoryStatus(1, '成功');
    }

    /**
     * Insert (idempotently) an mtb_customer_status row so the FK from
     * dtb_customer.customer_status_id → mtb_customer_status.id can be
     * satisfied.
     *
     * mtb_customer_status is empty in the structure-only schema dump.
     * EC-CUBE 4.3 ships exactly three rows: 1 = 仮会員 (provisional —
     * email unverified), 2 = 本会員 (active — verified), 3 = 退会
     * (withdrawn). Those are the only `customerStatus` values
     * {@see \MyVendor\BeMart\Be\Reason\Query\SqlCustomerCommand} ever
     * writes (a fresh registration → 2, activation flips 1→2,
     * withdrawal → 3). {@see seedCustomerStatus} seeds all three.
     */
    protected function insertCustomerStatus(int $id, string $name): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO mtb_customer_status '
            . '(id, name, sort_no, discriminator_type) '
            . 'VALUES (:id, :name, :sort_no, :discriminator)',
        );
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':sort_no' => $id,
            ':discriminator' => 'customerstatus',
        ]);
    }

    /**
     * Seed the master table dtb_customer's FK references
     * (mtb_customer_status) with the EC-CUBE 4.3 canonical rows.
     *
     * The structure-only schema dump leaves mtb_customer_status empty,
     * and `dtb_customer.customer_status_id` is a (nullable) FK to it —
     * so any SQL test that inserts a dtb_customer row with a NON-NULL
     * customer_status_id (every {@see \MyVendor\BeMart\Be\Reason\Query\SqlCustomerCommand}
     * write does — registration writes 2, withdrawal 3) MUST call this
     * first. Idempotent (`INSERT IGNORE`), so calling it once in setUp
     * is sufficient. Same precedent {@see seedAdminMasters} /
     * {@see seedLoginHistoryStatus} set for the analogous empty-master
     * FK case.
     */
    protected function seedCustomerStatus(): void
    {
        $this->insertCustomerStatus(1, '仮会員');
        $this->insertCustomerStatus(2, '本会員');
        $this->insertCustomerStatus(3, '退会');
    }

    /**
     * Insert a dtb_login_history row (one admin-login-attempt audit
     * record). Returns the inserted id.
     *
     * dtb_login_history's NOT NULL columns are login_history_status_id
     * (smallint unsigned, FK to mtb_login_history_status, no DEFAULT),
     * create_date, update_date, discriminator_type. `member_id` (FK to
     * dtb_member), `user_name` and `client_ip` are nullable. The
     * defaults match SqlLoginHistoryStorage's INSERT contract:
     * member_id = NULL (dtb_member is empty in the structure-only dump
     * so any non-NULL value would raise FK 1452 — resolving loginId →
     * member_id is Phase-2 scope), login_history_status_id = 1
     * (SUCCESS), discriminator_type = 'login_history'.
     *
     * Callers MUST seed mtb_login_history_status first via
     * {@see seedLoginHistoryStatus} — the FK is NOT NULL and the master
     * table is empty in the dump. Pass `login_history_status_id` => 0
     * to mimic a failed attempt.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertLoginHistory(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'login_history_status_id' => 1,
            'member_id' => null,
            'user_name' => sprintf('admin-%d', $counter),
            'client_ip' => '192.0.2.1',
            'create_date' => $now,
            'update_date' => $now,
            'discriminator_type' => 'login_history',
        ], $overrides);

        $this->executeInsert('dtb_login_history', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert a dtb_payment row (one admin-editable payment-method
     * master record). Returns the inserted id.
     *
     * dtb_payment's NOT NULL columns are id (AUTO_INCREMENT), fixed
     * (tinyint(1) DEFAULT 1), visible (tinyint(1) DEFAULT 1),
     * create_date, update_date, discriminator_type. `creator_id` (FK to
     * dtb_member), `payment_method`, `charge` (decimal, DEFAULT 0.00),
     * `rule_max`, `sort_no`, `payment_image`, `rule_min`,
     * `method_class` are all nullable. The defaults match
     * SqlPaymentMethodAdminStorage's INSERT contract: creator_id = NULL
     * (dtb_member is empty in the structure-only dump so any non-NULL
     * value would raise FK 1452), sort_no / payment_image /
     * method_class = NULL (no UI in the BeMart admin slice), fixed = 1
     * (schema default), visible = 1 (default-shown),
     * discriminator_type = 'payment'.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     */
    protected function insertPayment(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'creator_id' => null,
            'payment_method' => sprintf('Payment %d', $counter),
            'charge' => 0,
            'rule_max' => null,
            'sort_no' => null,
            'fixed' => 1,
            'payment_image' => null,
            'rule_min' => null,
            'method_class' => null,
            'visible' => 1,
            'create_date' => $now,
            'update_date' => $now,
            'discriminator_type' => 'payment',
        ], $overrides);

        $this->executeInsert('dtb_payment', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert (idempotently) an mtb_csv_type row so the FK from
     * dtb_csv.csv_type_id → mtb_csv_type.id can be satisfied.
     *
     * mtb_csv_type is empty in the structure-only schema dump and
     * `dtb_csv.csv_type_id` carries an enforced FK (FK_F55F48C3E8507796).
     * EC-CUBE 4.3 ships exactly four rows: 1 = 注文CSV, 2 = 会員CSV,
     * 3 = 商品CSV, 4 = 出荷CSV — the only `csvType` values
     * {@see \MyVendor\BeMart\Be\Reason\Query\SqlCsvColumnConfigStorage}
     * ever writes. {@see seedCsvTypes} seeds all four.
     */
    protected function insertCsvType(int $id, string $name): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO mtb_csv_type (id, name, sort_no, discriminator_type) '
            . 'VALUES (:id, :name, :sort_no, :discriminator)',
        );
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':sort_no' => $id,
            ':discriminator' => 'csvtype',
        ]);
    }

    /**
     * Seed the master table dtb_csv's FK reference (mtb_csv_type) with
     * the EC-CUBE 4.3 canonical rows.
     *
     * The structure-only schema dump leaves mtb_csv_type empty, and
     * `dtb_csv.csv_type_id` is a (NOT NULL-on-write) FK to it — so any
     * SQL test that inserts a dtb_csv row MUST call this first.
     * Idempotent (`INSERT IGNORE`), so calling it once in setUp is
     * sufficient. Same precedent {@see seedAdminMasters} /
     * {@see seedSaleTypes} / {@see seedLoginHistoryStatus} set for the
     * analogous empty-master FK case.
     */
    protected function seedCsvTypes(): void
    {
        $this->insertCsvType(1, '注文CSV');
        $this->insertCsvType(2, '会員CSV');
        $this->insertCsvType(3, '商品CSV');
        $this->insertCsvType(4, '出荷CSV');
    }

    /**
     * Insert a dtb_csv row (one CSV column-config entry). Returns the
     * inserted id.
     *
     * dtb_csv's NOT NULL columns are entity_name, field_name, disp_name,
     * sort_no (smallint unsigned, no DEFAULT), enabled (tinyint(1)
     * DEFAULT 1), create_date, update_date, discriminator_type.
     * `csv_type_id` (FK to mtb_csv_type) and `creator_id` (FK to
     * dtb_member) and `reference_field_name` are nullable.
     *
     * The defaults match {@see \MyVendor\BeMart\Be\Reason\Query\SqlCsvColumnConfigStorage}'s
     * INSERT contract: creator_id = NULL (dtb_member is empty in the
     * structure-only dump so any non-NULL value would raise FK 1452),
     * reference_field_name = NULL, entity_name / disp_name echo
     * field_name (the Wave 9 Entity carries only field_name as
     * columnName — the column catalog supplying the real entity/display
     * names is Phase 2), discriminator_type = 'csv'.
     *
     * Callers MUST seed mtb_csv_type first via {@see seedCsvTypes} — the
     * csv_type_id FK is enforced and the master table is empty in the
     * dump.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     *   Recognised keys: `csv_type_id`, `field_name`, `sort_no`,
     *   `enabled`, `entity_name`, `disp_name`.
     */
    protected function insertCsvColumn(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $fieldName = $overrides['field_name'] ?? sprintf('column_%d', $counter);
        $row = array_merge([
            'csv_type_id' => 3,
            'creator_id' => null,
            'entity_name' => $fieldName,
            'field_name' => $fieldName,
            'reference_field_name' => null,
            'disp_name' => $fieldName,
            'sort_no' => $counter,
            'enabled' => 1,
            'create_date' => $now,
            'update_date' => $now,
            'discriminator_type' => 'csv',
        ], $overrides);

        $this->executeInsert('dtb_csv', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert a dtb_plugin row (one plugin registry entry). Returns the
     * inserted id.
     *
     * dtb_plugin's NOT NULL columns are name, code, enabled (tinyint(1)
     * DEFAULT 0), version, source, initialized (tinyint(1) DEFAULT 0),
     * create_date, update_date, discriminator_type. There is no
     * AUTO-defaulted `source` — the column is NOT NULL with no DEFAULT,
     * so the helper supplies 0 (the EC-CUBE store-download origin).
     *
     * The defaults match {@see \MyVendor\BeMart\Be\Reason\Query\SqlPluginStorage}'s
     * INSERT contract: source = 0, discriminator_type = 'plugin'. Note
     * the BeMart {@see \MyVendor\BeMart\Be\Reason\Entity\PluginEntity}
     * `installed` axis maps onto `initialized` — pass `initialized` => 1
     * to mimic an installed plugin, `enabled` => 1 for an enabled one.
     *
     * @param array<string, mixed> $overrides Per-column overrides.
     *   Use the schema column names (snake_case: `code`, `name`,
     *   `version`, `enabled`, `initialized`).
     */
    protected function insertPlugin(array $overrides = []): int
    {
        static $counter = 0;
        $counter++;

        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'name' => sprintf('Plugin %d', $counter),
            'code' => sprintf('Vendor/Plugin%d', $counter),
            'enabled' => 0,
            'version' => '1.0.0',
            'source' => 0,
            'initialized' => 0,
            'create_date' => $now,
            'update_date' => $now,
            'discriminator_type' => 'plugin',
        ], $overrides);

        $this->executeInsert('dtb_plugin', $row);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Seed dtb_plugin with the two demo plugins {@see \MyVendor\BeMart\Be\Reason\Query\FakePluginStorage}
     * carries, so a SQL-backed hypermedia test starts from the same
     * client-observable baseline as the Fake-backed contract test:
     *
     *   - `Sample/SamplePlugin`   — installed + enabled
     *   - `Sample/DisabledPlugin` — installed + disabled
     *
     * `installed` maps onto `dtb_plugin.initialized` (see SqlPluginStorage
     * class doc) — both seeds are written `initialized=1`. The
     * structure-only schema dump leaves dtb_plugin empty, so any SQL
     * test that mirrors the AdminPlugin* Resource contract MUST call
     * this first. Same empty-table seed precedent as {@see seedCsvTypes}
     * — though dtb_plugin has no FK constraints, so this seeds the
     * fixture rows directly rather than a master table.
     */
    protected function seedPlugins(): void
    {
        $this->insertPlugin([
            'code' => 'Sample/SamplePlugin',
            'name' => 'Sample Plugin',
            'version' => '1.0.0',
            'initialized' => 1,
            'enabled' => 1,
        ]);
        $this->insertPlugin([
            'code' => 'Sample/DisabledPlugin',
            'name' => 'Disabled Sample Plugin',
            'version' => '1.0.0',
            'initialized' => 1,
            'enabled' => 0,
        ]);
    }

    /**
     * Insert a dtb_customer_favorite_product row. Returns the new id.
     *
     * create_date / update_date are NOT NULL — populate with now().
     */
    protected function insertFavorite(int $customerId, int $productId): int
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO dtb_customer_favorite_product '
            . '(customer_id, product_id, create_date, update_date, discriminator_type) '
            . 'VALUES (:customer_id, :product_id, :created, :updated, :discriminator)',
        );
        $stmt->execute([
            ':customer_id' => $customerId,
            ':product_id' => $productId,
            ':created' => $now,
            ':updated' => $now,
            ':discriminator' => 'customerfavoriteproduct',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Shared INSERT helper. Backticks every column to allow MySQL
     * reserved words and binds via named placeholders.
     *
     * @param array<string, mixed> $row
     */
    private function executeInsert(string $table, array $row): void
    {
        // Lightweight guard — surface the trait's PDO requirement loudly
        // if a consumer forgets to assign it.
        if (! isset($this->pdo)) {
            throw new RuntimeException(sprintf(
                '%s requires $this->pdo to be set before calling fixture helpers.',
                static::class,
            ));
        }

        $columns = array_keys($row);
        $placeholders = array_map(static fn (string $c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(static fn (string $c) => '`' . $c . '`', $columns)),
            implode(', ', $placeholders),
        );
        $stmt = $this->pdo->prepare($sql);
        $params = [];
        foreach ($row as $col => $value) {
            $params[':' . $col] = $value;
        }

        $stmt->execute($params);
    }
}
