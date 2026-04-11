<?php

declare(strict_types=1);

namespace MigrationOrchestrator;

final class JsonFile
{
    public static function decodeFile(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('JSON file not found: %s', $path));
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException(sprintf('Failed to read JSON file: %s', $path));
        }

        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new \RuntimeException(sprintf('Expected JSON object/array in: %s', $path));
        }

        return $decoded;
    }

    public static function encodeFile(string $path, array $data): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Failed to create directory: %s', $directory));
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $temporaryPath = $path . '.tmp';
        if (file_put_contents($temporaryPath, $json . PHP_EOL) === false) {
            throw new \RuntimeException(sprintf('Failed to write JSON file: %s', $path));
        }

        if (!rename($temporaryPath, $path)) {
            @unlink($temporaryPath);
            throw new \RuntimeException(sprintf('Failed to move JSON file into place: %s', $path));
        }
    }

    public static function appendNdjson(string $path, array $record): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Failed to create directory: %s', $directory));
        }

        $line = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
        if (file_put_contents($path, $line, FILE_APPEND | LOCK_EX) === false) {
            throw new \RuntimeException(sprintf('Failed to append NDJSON record: %s', $path));
        }
    }
}

