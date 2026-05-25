<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Override;
use PDO;
use Ray\Di\ProviderInterface;
use RuntimeException;

use function getenv;
use function is_string;
use function ltrim;
use function parse_str;
use function parse_url;
use function sprintf;

/**
 * Production PDO provider — Phase 2c.
 *
 * Builds the single MySQL/MariaDB connection that every `Sql*` Reason
 * implementation is constructed against under the `prod` context.
 *
 * The connection is derived from the `DATABASE_URL` environment variable
 * read at runtime — the same variable `phpunit.xml` injects for the
 * `bemart-sql` suite, but here taken from the real process environment so
 * production points at the real EC-CUBE database rather than
 * `eccubedb_test`.
 *
 * PDO attributes mirror the connection the `bemart-sql` suite proved the
 * SQL impls green against (see `be/tests/Sql/bootstrap.php`):
 *   - ATTR_ERRMODE       => ERRMODE_EXCEPTION  (fail loud)
 *   - ATTR_EMULATE_PREPARES => false           (LIMIT/OFFSET ints are
 *       bound as real ints — the SQL impls were written and tested
 *       against emulate-prepares-off; do not change this)
 *   - ATTR_DEFAULT_FETCH_MODE => FETCH_ASSOC   (the SQL impls expect
 *       associative rows)
 *
 * {@see SqlModule} binds this provider to `PDO::class` as a Singleton, so
 * one connection is shared across an entire request lifecycle.
 *
 * @implements ProviderInterface<PDO>
 */
final class PdoProvider implements ProviderInterface
{
    #[Override]
    public function get(): PDO
    {
        $databaseUrl = getenv('DATABASE_URL');
        if ($databaseUrl === false || $databaseUrl === '') {
            throw new RuntimeException(
                'DATABASE_URL is not set — the prod context requires a '
                . 'mysql:// DSN (e.g. '
                . 'mysql://user:pass@127.0.0.1:3306/eccubedb).',
            );
        }

        $parts = parse_url($databaseUrl);
        if (
            $parts === false
            || ! isset($parts['host'], $parts['user'], $parts['path'])
        ) {
            throw new RuntimeException(
                sprintf('DATABASE_URL is malformed: %s', $databaseUrl),
            );
        }

        $host = $parts['host'];
        $port = $parts['port'] ?? 3306;
        $user = $parts['user'];
        $pass = $parts['pass'] ?? '';
        $dbName = ltrim($parts['path'], '/');

        if ($dbName === '') {
            throw new RuntimeException(
                'DATABASE_URL has no database name in its path.',
            );
        }

        $charset = 'utf8mb4';
        if (isset($parts['query']) && is_string($parts['query'])) {
            parse_str($parts['query'], $query);
            if (isset($query['charset']) && is_string($query['charset']) && $query['charset'] !== '') {
                $charset = $query['charset'];
            }
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $dbName,
            $charset,
        );

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
