<?php

declare(strict_types=1);

/**
 * BeMart SQL test bootstrap — shared by:
 *
 *   - be/tests/Sql/*Test.php  (storage unit / AbstractSqlTestCase)
 *   - tests/Resource/Sql/*ResourceSqlTest.php  (Resource hypermedia / AbstractResourceSqlTestCase)
 *
 * Both base cases require this file exactly once per PHPUnit run via:
 *
 *   if (! isset($GLOBALS['BEMART_SQL_BOOTSTRAP'])) {
 *       require __DIR__ . '/../be/tests/Sql/bootstrap.php';   // from tests/Resource/Sql/
 *       // or
 *       require __DIR__ . '/bootstrap.php';                   // from be/tests/Sql/
 *   }
 *
 * After this file executes, $GLOBALS['BEMART_SQL_BOOTSTRAP'] is always set:
 *
 *   ['skip' => true,  'reason' => '<why>']                  — suite should skip
 *   ['skip' => false, 'pdo'    => PDO $instance]            — suite may run; use this PDO
 *
 * Skip conditions (silent, no failure):
 *   - DATABASE_URL environment variable is unset or empty
 *   - The server described by DATABASE_URL is unreachable
 *   - The connected server is neither MySQL 8.0+ nor MariaDB 10.11+
 *
 * Hard-failure condition (smoke fail — not a skip):
 *   - A supported server is reachable but schema load fails
 *
 * When the bootstrap runs for real (MySQL 8.0+ or MariaDB 10.11+ reachable):
 *   1. Drops and re-creates `eccubedb_test`  (utf8mb4 / utf8mb4_bin)
 *   2. Loads sql/schema/bemart-schema.sql wrapped in SET FOREIGN_KEY_CHECKS=0/1
 *   3. Loads seed/mtb-master.sql (mtb_* reference data)
 *   4. Loads seed/dtb-system-master.sql (installer-level system rows)
 *   5. Stores the open PDO in $GLOBALS so every test reuses the same connection
 *
 * Each test opens its own transaction in setUp() and rolls it back in tearDown(),
 * so tests are isolated without re-running this bootstrap.
 */

// Guard: run only once per PHP process.
if (isset($GLOBALS['BEMART_SQL_BOOTSTRAP'])) {
    return;
}

// ── helpers ──────────────────────────────────────────────────────────────────

/** Mark the suite as skipped with an explanatory reason. */
$skip = static function (string $reason): void {
    $GLOBALS['BEMART_SQL_BOOTSTRAP'] = ['skip' => true, 'reason' => $reason];
};

/** Hard-fail the run (schema / seed load error after a successful connection). */
$fail = static function (string $message): never {
    throw new RuntimeException('BeMart SQL bootstrap: ' . $message);
};

// ── 1. DATABASE_URL guard ────────────────────────────────────────────────────

$dsnRaw = (string) ($_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? '');
if ($dsnRaw === '') {
    $skip('DATABASE_URL is not set — SQL suite disabled');
    return;
}

// ── 2. Parse DATABASE_URL → PDO DSN ─────────────────────────────────────────
// Expected form: mysql://user[:pass]@host[:port]/dbname[?charset=...&serverVersion=...]

$parsed = parse_url($dsnRaw);
if ($parsed === false || ! isset($parsed['host'], $parsed['path'])) {
    $skip('DATABASE_URL cannot be parsed as a mysql:// URL — SQL suite disabled');
    return;
}

$dbUser = rawurldecode($parsed['user'] ?? 'root');
$dbPass = rawurldecode($parsed['pass'] ?? '');
$dbHost = $parsed['host'];
$dbPort = (int) ($parsed['port'] ?? 3306);
$dbName = ltrim($parsed['path'], '/');

// Strip the dbname from the DSN so we can connect without specifying a database
// (needed for the DROP / CREATE step).
$dsnBase = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $dbHost, $dbPort);
$dsnDb   = $dsnBase . ';dbname=' . $dbName;

// ── 3. Connectivity check ────────────────────────────────────────────────────

try {
    $pdo = new PDO($dsnBase, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    $skip(sprintf(
        'Cannot connect to %s@%s:%d — %s — SQL suite disabled',
        $dbUser,
        $dbHost,
        $dbPort,
        $e->getMessage(),
    ));
    return;
}

// ── 4. Server-version gate ───────────────────────────────────────────────────
// The SQL suite runs on MySQL 8.0+ or MariaDB 10.11+. bemart-schema.sql is
// plain MySQL/MariaDB-compatible SQL and var/sql/*.sql uses only functions
// available on both (JSON_VALUE via CAST, JSON_TABLE, GROUP_CONCAT ... ORDER BY).
// Skip cleanly on older servers (e.g. MySQL 5.x) that lack them.

$versionRow = $pdo->query('SELECT VERSION() AS v')->fetch();
$serverVersion = (string) ($versionRow['v'] ?? '');

$isMariaDb = stripos($serverVersion, 'mariadb') !== false;
$isMysql8Plus = (bool) preg_match('/^(8|9|1\d)\./', $serverVersion);
if (! $isMariaDb && ! $isMysql8Plus) {
    $skip(sprintf(
        'current server is %s, SQL suite requires MySQL 8.0+ or MariaDB — SQL suite disabled',
        $serverVersion,
    ));
    return;
}

// ── 5. Drop + recreate test database ─────────────────────────────────────────

try {
    $pdo->exec(sprintf('DROP DATABASE IF EXISTS `%s`', $dbName));
    $pdo->exec(sprintf(
        'CREATE DATABASE `%s` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin',
        $dbName,
    ));
    $pdo->exec(sprintf('USE `%s`', $dbName));
} catch (PDOException $e) {
    $fail('failed to (re)create database ' . $dbName . ': ' . $e->getMessage());
}

// ── 6. Load schema (FK checks off) ───────────────────────────────────────────

$projectRoot = dirname(__DIR__, 3);
$schemaFile  = $projectRoot . '/sql/schema/bemart-schema.sql';
$seedMtb     = $projectRoot . '/sql/seed/mtb-master.sql';
$seedDtb     = $projectRoot . '/sql/seed/dtb-system-master.sql';

foreach ([$schemaFile, $seedMtb, $seedDtb] as $requiredFile) {
    if (! is_readable($requiredFile)) {
        $fail('required file missing or unreadable: ' . $requiredFile);
    }
}

$loadSql = static function (PDO $pdo, string $path) use ($fail): void {
    $sql = file_get_contents($path);
    if ($sql === false) {
        $fail('cannot read file: ' . $path);
    }

    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        // PDO::exec() does not support multi-statement by default; split on ';'
        // boundaries that end a logical statement (not inside strings/comments).
        // For our schema and seed files — which use simple single-line statements
        // without embedded semicolons in string literals — a split on ";\n" is
        // sufficient and avoids pulling in a full SQL parser.
        foreach (explode(";\n", $sql) as $statement) {
            $statement = trim($statement);
            if ($statement === '' || str_starts_with($statement, '--')) {
                continue;
            }

            $pdo->exec($statement);
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    } catch (PDOException $e) {
        $fail(sprintf('schema/seed load error in %s: %s', basename($path), $e->getMessage()));
    }
};

$loadSql($pdo, $schemaFile);
$loadSql($pdo, $seedMtb);
$loadSql($pdo, $seedDtb);

// ── 7. Reconnect with dbname for test use ────────────────────────────────────
// Re-open so every subsequent query lands in the correct database without
// needing an explicit USE statement in every test.

try {
    $pdo = new PDO($dsnDb, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    $fail('cannot reconnect to ' . $dbName . ' after setup: ' . $e->getMessage());
}

// ── 8. Publish result ────────────────────────────────────────────────────────

$GLOBALS['BEMART_SQL_BOOTSTRAP'] = ['skip' => false, 'pdo' => $pdo];
