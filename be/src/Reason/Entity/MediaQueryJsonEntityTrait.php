<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use JsonException;
use ReflectionClass;
use ReflectionNamedType;

use function array_key_exists;
use function array_map;
use function array_values;
use function get_object_vars;
use function is_array;
use function is_scalar;
use function json_decode;
use function json_encode;
use function ltrim;
use function trim;

use const JSON_THROW_ON_ERROR;

/**
 * Ray.MediaQuery parameter/result bridge for readonly domain DTOs.
 *
 * Domain entities intentionally do not use #[Input].  When an entity is passed
 * to a direct #[DbQuery] method, ParamConverter reduces it through
 * ToScalarInterface to one JSON scalar.  The same trait also exposes a native
 * static factory, so simple SELECT projections can hydrate entities without a
 * SQL-locator adapter class.
 */
trait MediaQueryJsonEntityTrait
{
    #[\Override]
    public function toScalar(): string
    {
        return json_encode($this->normalizeForJson(get_object_vars($this)), JSON_THROW_ON_ERROR);
    }

    /**
     * Native Ray.MediaQuery factory. Supports either one JSON column or a
     * positional row whose column order mirrors the constructor.
     *
     * @return static
     */
    public static function factory(mixed ...$values): self
    {
        $data = self::isJsonPayload($values)
            ? self::decodePayload($values[0])
            : self::positionalPayload(array_values($values));

        /** @psalm-suppress UnsafeInstantiation */
        return new self(...$data);
    }


    /** @param array<array-key, mixed> $values */
    private static function isJsonPayload(array $values): bool
    {
        if (count($values) !== 1 || ! is_scalar($values[0])) {
            return false;
        }

        $payload = trim((string) $values[0]);

        return $payload !== '' && ($payload[0] === '{' || $payload[0] === '[');
    }

    /** @return array<string, mixed> */
    private static function decodePayload(mixed $payload): array
    {
        if ($payload === null || $payload === '') {
            return [];
        }

        try {
            $decoded = json_decode((string) $payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decoded) ? self::coerceNamedPayload($decoded) : [];
    }

    /**
     * @param list<mixed> $values
     * @return array<string, mixed>
     */
    private static function positionalPayload(array $values): array
    {
        $constructor = (new ReflectionClass(self::class))->getConstructor();
        if ($constructor === null) {
            return [];
        }

        $payload = [];
        foreach ($constructor->getParameters() as $index => $parameter) {
            if (array_key_exists($index, $values)) {
                $payload[$parameter->getName()] = $values[$index];
            }
        }

        return self::coerceNamedPayload($payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private static function coerceNamedPayload(array $payload): array
    {
        $constructor = (new ReflectionClass(self::class))->getConstructor();
        if ($constructor === null) {
            return $payload;
        }

        $coerced = [];
        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (! array_key_exists($name, $payload)) {
                continue;
            }

            $coerced[$name] = self::coerceValue($payload[$name], $parameter->getType() instanceof ReflectionNamedType ? $parameter->getType() : null);
        }

        return $coerced;
    }

    private static function coerceValue(mixed $value, ReflectionNamedType|null $type): mixed
    {
        if ($value === null || $type === null) {
            return $value;
        }

        $name = ltrim($type->getName(), '\\');
        return match ($name) {
            'string' => (string) $value,
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            'array' => is_array($value) ? $value : [],
            DateTimeImmutable::class => $value instanceof DateTimeImmutable ? $value : new DateTimeImmutable((string) $value),
            DateTimeInterface::class => $value instanceof DateTimeInterface ? $value : new DateTimeImmutable((string) $value),
            default => $value,
        };
    }

    private function normalizeForJson(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalizeForJson($item), $value);
        }

        if (is_object($value)) {
            return $this->normalizeForJson(get_object_vars($value));
        }

        return $value;
    }
}
