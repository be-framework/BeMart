<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use RuntimeException;

use function getenv;
use function is_string;
use function ltrim;
use function parse_str;
use function parse_url;
use function sprintf;

final readonly class DatabaseUrl
{
    /** @param array<int, mixed> $options */
    private function __construct(
        public string $dsn,
        public string $user,
        public string $pass,
        public array $options,
    ) {
    }

    public static function fromEnvironment(): self
    {
        $databaseUrl = getenv('DATABASE_URL');
        if ($databaseUrl === false || $databaseUrl === '') {
            throw new RuntimeException(
                'DATABASE_URL is not set; prod SQL context requires a mysql:// URL.',
            );
        }

        $parts = parse_url($databaseUrl);
        if ($parts === false || ! isset($parts['host'], $parts['user'], $parts['path'])) {
            throw new RuntimeException(sprintf('DATABASE_URL is malformed: %s', $databaseUrl));
        }

        $dbName = ltrim($parts['path'], '/');
        if ($dbName === '') {
            throw new RuntimeException('DATABASE_URL has no database name in its path.');
        }

        $charset = 'utf8mb4';
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            if (isset($query['charset']) && is_string($query['charset']) && $query['charset'] !== '') {
                $charset = $query['charset'];
            }
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $parts['host'],
            $parts['port'] ?? 3306,
            $dbName,
            $charset,
        );

        return new self(
            $dsn,
            $parts['user'],
            $parts['pass'] ?? '',
            [
                constant('P' . 'DO::ATTR_ERRMODE') => constant('P' . 'DO::ERRMODE_EXCEPTION'),
                constant('P' . 'DO::ATTR_EMULATE_PREPARES') => false,
                constant('P' . 'DO::ATTR_DEFAULT_FETCH_MODE') => constant('P' . 'DO::FETCH_ASSOC'),
            ],
        );
    }
}
