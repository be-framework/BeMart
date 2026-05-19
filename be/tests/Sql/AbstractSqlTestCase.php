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
 * Subclasses access the shared connection via `$this->pdo` and may
 * seed rows via the fixture helpers exposed by {@see SqlFixturesTrait}
 * (insertCustomer / insertProduct / insertOrder / insertOrderItem /
 * insertCart / insertCartItem / insertFavorite / defaultProductClassId).
 *
 * Test surfaces under `be/tests/Sql/`:
 *   - `Sql*Test.php`                — storage-layer unit (1 class under
 *     test, no Be Final, no injector).
 *   - `*SqlIntegrationTest.php`     — Final-direct integration (Final
 *     constructor wired with Sql backends manually, no injector — the
 *     fastest end-to-end smoke).
 *
 * Resource-layer hypermedia tests under `tests/Resource/Sql/` use a
 * sibling base class (`AbstractResourceSqlTestCase`) that shares the
 * same fixture trait but layers an Injector + AppModule on top so the
 * full Becoming chain is exercised through `ResourceInterface::get(...)`.
 */
abstract class AbstractSqlTestCase extends TestCase
{
    use SqlFixturesTrait;

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
}
