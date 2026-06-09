<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Smoke;

use Aura\Sql\ExtendedPdoInterface;
use DateTimeImmutable;
use DateTimeInterface;
use MyVendor\BeMart\Module\MediaQueryQueries;
use MyVendor\BeMart\Module\MediaQueryRuntimeModule;
use PDOException;
use Ray\Di\Injector;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\ParamConverterInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use RuntimeException;

use function array_filter;
use function array_map;
use function array_pop;
use function basename;
use function dirname;
use function explode;
use function file_get_contents;
use function glob;
use function in_array;
use function is_a;
use function is_array;
use function ksort;
use function preg_match;
use function sort;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function trim;

/** Builds the DB-backed, prepare-only SQL smoke surface for MediaQuery files. */
final class MediaQuerySqlSmokeHelper
{
    private static Injector|null $injector = null;
    private static ExtendedPdoInterface|null $connection = null;

    /** @return array<string, array{0: string, 1: ReflectionMethod}> */
    public static function cases(): array
    {
        $cases = [];
        foreach (MediaQueryQueries::classes() as $class) {
            $reflection = new ReflectionClass($class);
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $attributes = $method->getAttributes(DbQuery::class);
                if ($attributes === []) {
                    continue;
                }

                $sqlId = $attributes[0]->newInstance()->id;
                $cases[$sqlId] = [$sqlId, $method];
            }
        }

        ksort($cases);

        return $cases;
    }

    public static function connection(): ExtendedPdoInterface
    {
        if (self::$connection instanceof ExtendedPdoInterface) {
            return self::$connection;
        }

        try {
            $connection = self::injector()->getInstance(ExtendedPdoInterface::class);
            $connection->perform('SELECT 1');
        } catch (PDOException|RuntimeException $e) {
            throw new RuntimeException(
                'DB-backed MediaQuery SQL smoke requires DATABASE_URL to point to a prepared EC-CUBE test database; ' .
                'run sql/setup-db.sh for that database first. Connection check failed: ' . $e->getMessage(),
                previous: $e,
            );
        }

        self::$connection = $connection;

        return $connection;
    }

    /** @return array<string, mixed> */
    public static function convertedValues(ReflectionMethod $method): array
    {
        $values = [];
        foreach ($method->getParameters() as $parameter) {
            $values[$parameter->getName()] = self::valueForParameter($parameter);
        }

        $converter = self::injector()->getInstance(ParamConverterInterface::class);
        $converter($values);

        return $values;
    }

    public static function sqlFile(string $sqlId): string
    {
        return dirname(__DIR__, 2) . '/var/sql/' . $sqlId . '.sql';
    }

    /** @return list<string> */
    public static function statements(string $sqlFile): array
    {
        $sql = (string) file_get_contents($sqlFile);
        if (! str_contains($sql, ';')) {
            $sql .= ';';
        }

        $statements = explode(';', trim($sql, "\\ \t\n\r\0\x0B"));
        array_pop($statements);

        return array_values(array_filter(
            array_map(static fn (string $statement): string => trim($statement), $statements),
            static fn (string $statement): bool => $statement !== '',
        ));
    }

    /** @return list<string> */
    public static function sqlIds(): array
    {
        $files = glob(dirname(__DIR__, 2) . '/var/sql/*.sql');
        if (! is_array($files)) {
            return [];
        }

        $ids = array_map(static fn (string $file): string => basename($file, '.sql'), $files);
        sort($ids);

        return $ids;
    }

    private static function injector(): Injector
    {
        if (self::$injector instanceof Injector) {
            return self::$injector;
        }

        self::$injector = new Injector(
            new MediaQueryRuntimeModule(),
            dirname(__DIR__, 2) . '/var/tmp/sql-smoke',
        );

        return self::$injector;
    }

    private static function valueForParameter(ReflectionParameter $parameter): mixed
    {
        return self::valueFor(
            $parameter->getName(),
            $parameter->getType(),
            $parameter->isDefaultValueAvailable(),
            $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
        );
    }

    private static function valueFor(
        string $name,
        ReflectionType|null $type,
        bool $hasDefault = false,
        mixed $default = null,
    ): mixed {
        if ($hasDefault) {
            return $default;
        }

        if ($type instanceof ReflectionUnionType) {
            return self::valueForUnion($name, $type);
        }

        if (! $type instanceof ReflectionNamedType) {
            return self::stringValue($name);
        }

        if ($type->allowsNull() && self::nullableCanStayNull($name)) {
            return null;
        }

        if ($type->isBuiltin()) {
            return self::builtinValue($name, $type->getName());
        }

        return self::objectValue($type->getName());
    }

    private static function valueForUnion(string $name, ReflectionUnionType $type): mixed
    {
        $names = array_map(static fn (ReflectionNamedType $named): string => $named->getName(), $type->getTypes());
        if (in_array(DateTimeImmutable::class, $names, true) || in_array(DateTimeInterface::class, $names, true)) {
            return self::dateValue();
        }

        foreach (['bool', 'float', 'string', 'int'] as $builtin) {
            if (in_array($builtin, $names, true)) {
                return self::builtinValue($name, $builtin);
            }
        }

        foreach ($type->getTypes() as $named) {
            if (! $named->isBuiltin()) {
                return self::objectValue($named->getName());
            }
        }

        return null;
    }

    private static function builtinValue(string $name, string $type): mixed
    {
        return match ($type) {
            'array' => [],
            'bool' => true,
            'float' => 10.0,
            'int' => str_ends_with($name, 'offset') || $name === 'offset' ? 0 : 1,
            'string' => self::stringValue($name),
            default => null,
        };
    }

    /** @param class-string $class */
    private static function objectValue(string $class): object
    {
        if ($class === DateTimeImmutable::class || is_a($class, DateTimeInterface::class, true)) {
            return new DateTimeImmutable(self::dateValue());
        }

        $reflection = new ReflectionClass($class);
        if (! $reflection->isInstantiable()) {
            throw new RuntimeException(sprintf('Cannot build smoke value for non-instantiable parameter type %s', $class));
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $args = [];
        foreach ($constructor->getParameters() as $parameter) {
            $args[] = self::valueForParameter($parameter);
        }

        return $reflection->newInstanceArgs($args);
    }

    private static function nullableCanStayNull(string $name): bool
    {
        return ! str_ends_with($name, 'Id') && ! str_contains(strtolower($name), 'code') && $name !== 'email';
    }

    private static function stringValue(string $name): string
    {
        $lower = strtolower($name);
        if (str_ends_with($name, 'Id') || preg_match('/(^id$|Id$)/', $name) === 1) {
            return '1';
        }

        if (str_contains($lower, 'email')) {
            return 'smoke@example.com';
        }

        if (str_contains($lower, 'date') || $lower === 'timestamp' || str_ends_with($lower, 'at')) {
            return self::dateValue();
        }

        if (str_contains($lower, 'code')) {
            return 'CODE000001';
        }

        if (str_contains($lower, 'url')) {
            return 'https://example.com/smoke';
        }

        if ($lower === 'version') {
            return '1.0.0';
        }

        if (str_starts_with($lower, 'order')) {
            return 'ORDER-SMOKE-1';
        }

        return 'smoke-' . $name;
    }

    private static function dateValue(): string
    {
        return '2024-01-02 00:00:00';
    }
}
