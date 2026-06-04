<?php

/**
 * SQL test bootstrap — Phase 2a Step 2.
 *
 * Creates a deterministic `eccubedb_test` database from
 * sql/schema/ec-cube-4.3-mysql-mysqldump.sql on each PHPUnit run.
 *
 * Behavior:
 * - If DATABASE_URL is unset → write a marker so AbstractSqlTestCase
 *   can skip cleanly. (This keeps the suite useful in environments
 *   without MariaDB, e.g. early CI.)
 * - If DATABASE_URL is set but the server is unreachable, or the
 *   schema fails to load → die loudly with a clear message. This is
 *   a smoke test: silent skips defeat its purpose.
 */

declare(strict_types=1);

$beAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
$rootAutoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
require_once is_file($beAutoload) ? $beAutoload : $rootAutoload;

$databaseUrl = getenv('DATABASE_URL');
if ($databaseUrl === false || $databaseUrl === '') {
    // No DB configured — leave a flag and let tests skip themselves.
    $GLOBALS['BEMART_SQL_BOOTSTRAP'] = ['skip' => true, 'reason' => 'DATABASE_URL not set'];

    return;
}

$parts = parse_url($databaseUrl);
if ($parts === false || ! isset($parts['scheme'], $parts['host'], $parts['user'], $parts['path'])) {
    fwrite(STDERR, "[bemart-sql] DATABASE_URL is malformed: {$databaseUrl}\n");
    exit(1);
}

$host = $parts['host'];
$port = $parts['port'] ?? 3306;
$user = $parts['user'];
$pass = $parts['pass'] ?? '';
$dbName = ltrim($parts['path'], '/');

if ($dbName === '') {
    fwrite(STDERR, "[bemart-sql] DATABASE_URL has no database name in path\n");
    exit(1);
}

// Connect to the server itself (no default schema) so we can DROP + CREATE.
$serverDsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port);

try {
    $admin = new PDO($serverDsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, sprintf(
        "[bemart-sql] Cannot connect to MariaDB at %s:%d as %s — %s\n"
        . "Hint: start the server (e.g. `sudo service mariadb start`) "
        . "or unset DATABASE_URL to skip the SQL suite.\n",
        $host,
        $port,
        $user,
        $e->getMessage(),
    ));
    exit(1);
}

$quotedDb = '`' . str_replace('`', '``', $dbName) . '`';
$admin->exec("DROP DATABASE IF EXISTS {$quotedDb}");
$admin->exec("CREATE DATABASE {$quotedDb} DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin");

// Load schema. The dump uses `mysql`-only constructs (CHARSET pragmas,
// SET NAMES, etc.) so the simplest reliable loader is the `mysql` CLI.
$schemaPath = dirname(__DIR__, 3) . '/sql/schema/ec-cube-4.3-mysql-mysqldump.sql';
if (! is_readable($schemaPath)) {
    fwrite(STDERR, "[bemart-sql] Schema file missing or unreadable: {$schemaPath}\n");
    exit(1);
}

// The dump has cross-table FOREIGN KEY constraints but no
// `SET FOREIGN_KEY_CHECKS=0` pragma at the top. Loading sequentially
// trips on the very first table (dtb_authority_role → dtb_member),
// so we wrap the load with FK checks disabled.
$cmd = sprintf(
    '(echo "SET FOREIGN_KEY_CHECKS=0;"; cat %s; echo "SET FOREIGN_KEY_CHECKS=1;") '
    . '| MYSQL_PWD=%s mysql -h %s -P %d -u %s %s 2>&1',
    escapeshellarg($schemaPath),
    escapeshellarg($pass),
    escapeshellarg($host),
    $port,
    escapeshellarg($user),
    escapeshellarg($dbName),
);
exec($cmd, $output, $exitCode);
if ($exitCode !== 0) {
    fwrite(STDERR, "[bemart-sql] Loading schema failed (exit {$exitCode}):\n"
        . implode("\n", $output) . "\n");
    exit(1);
}

// Build the shared connection used by AbstractSqlTestCase. Disable
// emulated prepares so LIMIT/OFFSET ints don't get quoted.
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $dbName);
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$GLOBALS['BEMART_SQL_BOOTSTRAP'] = ['skip' => false, 'pdo' => $pdo];
