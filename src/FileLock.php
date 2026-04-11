<?php

declare(strict_types=1);

namespace MigrationOrchestrator;

final class FileLock
{
    public static function withExclusiveLock(string $path, callable $callback): mixed
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Failed to create lock directory: %s', $directory));
        }

        $handle = fopen($path, 'c+');
        if ($handle === false) {
            throw new \RuntimeException(sprintf('Failed to open lock file: %s', $path));
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new \RuntimeException(sprintf('Failed to acquire lock: %s', $path));
            }

            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}

