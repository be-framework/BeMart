<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Base class for SQL tests. Each test runs inside a transaction that
 * tearDown rolls back — leaving the schema pristine without needing
 * to TRUNCATE 65 tables between tests.
 *
 * Subclasses access the shared connection via $this->pdo and may
 * seed rows via the insertCustomer() helper.
 */
abstract class AbstractSqlTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        // Lazy one-shot bootstrap. PHPUnit only loads `bootstrap.php`
        // (the global one) before tests; the SQL bootstrap lives next
        // to this file and is loaded the first time any SQL test runs.
        if (! isset($GLOBALS['BEMART_SQL_BOOTSTRAP'])) {
            require __DIR__ . '/bootstrap.php';
        }

        /** @var array{skip: bool, reason?: string, pdo?: PDO}|null $bootstrap */
        $bootstrap = $GLOBALS['BEMART_SQL_BOOTSTRAP'] ?? null;

        if ($bootstrap === null) {
            throw new RuntimeException('SQL bootstrap failed to populate $GLOBALS[\'BEMART_SQL_BOOTSTRAP\']');
        }

        if ($bootstrap['skip']) {
            $this->markTestSkipped($bootstrap['reason'] ?? 'SQL suite disabled');
        }

        $this->pdo = $bootstrap['pdo'];
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

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
        // secret_key is NOT NULL UNIQUE — generate a fresh hex per insert
        // unless the caller has supplied one explicitly.
        // customer_status_id is nullable AND has a FK to mtb_customer_status,
        // which the structure-only schema dump leaves empty. Default to
        // NULL; tests that care about status semantics can override.
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

        $columns = array_keys($row);
        $placeholders = array_map(static fn (string $c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO dtb_customer (%s) VALUES (%s)',
            implode(', ', array_map(static fn (string $c) => '`' . $c . '`', $columns)),
            implode(', ', $placeholders),
        );
        $stmt = $this->pdo->prepare($sql);
        $params = [];
        foreach ($row as $col => $value) {
            $params[':' . $col] = $value;
        }

        $stmt->execute($params);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insert a dtb_product row plus the default dtb_product_class row
     * (the one with `class_category_id1`/`class_category_id2` both NULL
     * — EC-CUBE's convention for products without variations). Returns
     * the new dtb_product.id.
     *
     * `dtb_product_class.price02` is NOT NULL; everything else on
     * product_class is nullable. We seed price02 with a deterministic
     * default but expose it via `$overrides['price02']`.
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

        // 1. Product header.
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

        // 2. Default class. product_code is what BeMart treats as the
        // public handle; price02 is the unit price.
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
     * Required NOT NULL columns we MUST satisfy: name01, name02,
     * subtotal, discount, delivery_fee_total, charge, tax, total,
     * payment_total, add_point, use_point, create_date, update_date,
     * discriminator_type.
     *
     * `customer_id` is nullable; tests that exercise listByCustomer
     * must override it with an int.
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

        // Filter out the orderNo synthetic so the column list matches.
        $columns = array_keys($row);
        $placeholders = array_map(static fn (string $c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO dtb_order (%s) VALUES (%s)',
            implode(', ', array_map(static fn (string $c) => '`' . $c . '`', $columns)),
            implode(', ', $placeholders),
        );
        $stmt = $this->pdo->prepare($sql);
        $params = [];
        foreach ($row as $col => $value) {
            $params[':' . $col] = $value;
        }

        $stmt->execute($params);

        return [
            'id' => (int) $this->pdo->lastInsertId(),
            'orderNo' => (string) $row['order_no'],
        ];
    }

    /**
     * Insert a dtb_order_item row. Returns the new id.
     *
     * Required NOT NULL columns: product_name, price, quantity, tax,
     * tax_rate, tax_adjust, discriminator_type.
     *
     * `order_item_type_id` is a NULLABLE FK to mtb_order_item_type;
     * the structure-only dump leaves the master table empty, so we
     * default to NULL (rather than the EC-CUBE PRODUCT(1) default
     * which would trip a FK violation).
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

        $columns = array_keys($row);
        $placeholders = array_map(static fn (string $c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO dtb_order_item (%s) VALUES (%s)',
            implode(', ', array_map(static fn (string $c) => '`' . $c . '`', $columns)),
            implode(', ', $placeholders),
        );
        $stmt = $this->pdo->prepare($sql);
        $params = [];
        foreach ($row as $col => $value) {
            $params[':' . $col] = $value;
        }

        $stmt->execute($params);

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
}
