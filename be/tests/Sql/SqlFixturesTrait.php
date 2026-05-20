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
     * @param array<string, mixed> $overrides Per-column overrides.
     *   Recognised keys: `name`, `product_code`, `price02`, `stock`,
     *   `stock_unlimited`.
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
            . '(product_id, product_code, price02, stock, stock_unlimited, '
            . 'visible, create_date, update_date, discriminator_type) '
            . 'VALUES (:product_id, :product_code, :price02, :stock, '
            . ':stock_unlimited, 1, :created, :updated, :discriminator)',
        );
        $stmt->execute([
            ':product_id' => $productId,
            ':product_code' => $productCode,
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
