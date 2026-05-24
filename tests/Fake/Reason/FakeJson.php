<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake;

use RuntimeException;

use function dirname;
use function file_get_contents;
use function is_array;
use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/** Loads immutable seed/example rows from be/var/fake. */
final class FakeJson
{
    private function __construct()
    {
    }

    /** @return list<array<string, mixed>> */
    public static function rows(string $fileName): array
    {
        $path = dirname(__DIR__, 3) . '/be/var/fake/' . $fileName;
        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException(sprintf('Fake fixture missing: %s', $path));
        }

        /** @var mixed $decoded */
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new RuntimeException(sprintf('Fake fixture must be a JSON array: %s', $path));
        }

        $rows = [];
        foreach ($decoded as $row) {
            if (! is_array($row)) {
                throw new RuntimeException(sprintf('Fake fixture rows must be objects: %s', $path));
            }

            /** @var array<string, mixed> $row */
            $rows[] = $row;
        }

        return $rows;
    }
}
