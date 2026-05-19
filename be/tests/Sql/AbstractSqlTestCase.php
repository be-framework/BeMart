<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

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
}
