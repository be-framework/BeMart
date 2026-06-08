<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Queries;
use ReflectionClass;
use ReflectionMethod;

use function dirname;
use function sort;

/** Discovers BeMart MediaQuery interfaces from #[DbQuery] methods. */
final class MediaQueryQueries
{
    public static function fromAppRoot(string|null $appRoot = null): Queries
    {
        return Queries::fromClasses(self::classes($appRoot));
    }

    /** @return list<class-string> */
    public static function classes(string|null $appRoot = null): array
    {
        $root = $appRoot ?? dirname(__DIR__, 2);
        $queries = Queries::fromDir($root . '/be/src/Reason/Query');
        $classes = [];
        foreach ($queries->classes as $class) {
            if (! self::hasDbQueryMethod($class)) {
                continue;
            }

            $classes[] = $class;
        }

        sort($classes);

        return $classes;
    }

    /** @param class-string $class */
    private static function hasDbQueryMethod(string $class): bool
    {
        $reflection = new ReflectionClass($class);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getAttributes(DbQuery::class) !== []) {
                return true;
            }
        }

        return false;
    }
}
