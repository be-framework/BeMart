<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Smoke;

use MyVendor\BeMart\Module\MediaQueryQueries;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\Annotation\DbQuery;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

use function array_diff;
use function array_keys;
use function array_map;
use function array_unique;
use function basename;
use function glob;
use function implode;
use function is_array;
use function sort;

#[CoversNothing]
final class MediaQueryCoverageTest extends TestCase
{
    public function testSqlStemAndDbQueryIdsMatch(): void
    {
        $sqlIds = self::sqlIds();
        $dbQueryIds = array_keys(self::dbQueryMethods());
        sort($sqlIds);
        sort($dbQueryIds);

        self::assertSame([], array_values(array_diff($sqlIds, $dbQueryIds)), 'SQL file without #[DbQuery] id');
        self::assertSame([], array_values(array_diff($dbQueryIds, $sqlIds)), '#[DbQuery] id without SQL file');
    }

    public function testDbQueryIdsAreUnique(): void
    {
        $seen = [];
        $duplicates = [];
        foreach (MediaQueryQueries::classes() as $class) {
            $reflection = new ReflectionClass($class);
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $attributes = $method->getAttributes(DbQuery::class);
                if ($attributes === []) {
                    continue;
                }

                $id = $attributes[0]->newInstance()->id;
                if (isset($seen[$id])) {
                    $duplicates[] = $id . ' (' . $seen[$id] . ', ' . $class . '::' . $method->getName() . ')';
                    continue;
                }

                $seen[$id] = $class . '::' . $method->getName();
            }
        }

        self::assertSame([], $duplicates);
    }

    public function testNonVoidDbQueryIdsHaveFakeFixtureAndNoExtraFixtureExists(): void
    {
        $required = [];
        foreach (self::dbQueryMethods() as $id => $method) {
            if (! self::isVoid($method)) {
                $required[] = $id;
            }
        }

        $fixtureIds = self::fixtureIds();
        sort($required);
        sort($fixtureIds);

        self::assertSame([], array_values(array_diff($required, $fixtureIds)), 'non-void #[DbQuery] id without fake fixture');
        self::assertSame([], array_values(array_diff($fixtureIds, $required)), 'fake fixture without non-void #[DbQuery] id');
    }

    /** @return array<string, ReflectionMethod> */
    private static function dbQueryMethods(): array
    {
        $methods = [];
        foreach (MediaQueryQueries::classes() as $class) {
            $reflection = new ReflectionClass($class);
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $attributes = $method->getAttributes(DbQuery::class);
                if ($attributes === []) {
                    continue;
                }

                $methods[$attributes[0]->newInstance()->id] = $method;
            }
        }

        return $methods;
    }

    /** @return list<string> */
    private static function sqlIds(): array
    {
        return self::stems(__DIR__ . '/../../var/sql/*.sql');
    }

    /** @return list<string> */
    private static function fixtureIds(): array
    {
        return array_unique([
            ...self::stems(__DIR__ . '/../../be/var/fake/query/*.json'),
            ...self::stems(__DIR__ . '/../../be/var/fake/query/*.jsonl'),
        ]);
    }

    /** @return list<string> */
    private static function stems(string $pattern): array
    {
        $files = glob($pattern);
        if (! is_array($files)) {
            return [];
        }

        $ids = array_map(static fn (string $file): string => basename($file, '.' . pathinfo($file, PATHINFO_EXTENSION)), $files);
        sort($ids);

        return $ids;
    }

    private static function isVoid(ReflectionMethod $method): bool
    {
        $type = $method->getReturnType();

        return $type instanceof ReflectionNamedType && $type->getName() === 'void';
    }
}
