<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Resource;

use function array_is_list;
use function array_key_exists;
use function array_keys;
use function class_exists;
use function explode;
use function get_debug_type;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_scalar;
use function is_string;
use function sprintf;

final readonly class ResourceSchema
{
    /**
     * @param array<string, string> $required
     * @param array<string, string> $optional
     */
    public function __construct(
        public string $name,
        private array $required,
        private array $optional = [],
        private bool $allowExtra = false,
    ) {
    }

    public function assertMatches(mixed $value): void
    {
        $errors = $this->validate($value);
        if ($errors !== []) {
            throw new ResourceSchemaViolationException($this->name, $errors);
        }
    }

    /** @return list<string> */
    public function validate(mixed $value): array
    {
        if (! is_array($value)) {
            return [sprintf('body must be array, got %s', get_debug_type($value))];
        }

        $errors = [];
        foreach ($this->required as $field => $type) {
            if (! array_key_exists($field, $value)) {
                $errors[] = sprintf('missing required field `%s`', $field);
                continue;
            }

            if (! self::matchesType($value[$field], $type)) {
                $errors[] = self::typeError($field, $type, $value[$field]);
            }
        }

        foreach ($this->optional as $field => $type) {
            if (! array_key_exists($field, $value)) {
                continue;
            }

            if (! self::matchesType($value[$field], $type)) {
                $errors[] = self::typeError($field, $type, $value[$field]);
            }
        }

        if (! $this->allowExtra) {
            $known = [...array_keys($this->required), ...array_keys($this->optional)];
            foreach (array_keys($value) as $field) {
                if (! in_array($field, $known, true)) {
                    $errors[] = sprintf('unexpected field `%s`', $field);
                }
            }
        }

        return $errors;
    }

    private static function matchesType(mixed $value, string $type): bool
    {
        foreach (explode('|', $type) as $candidate) {
            if (self::matchesSingleType($value, trim($candidate))) {
                return true;
            }
        }

        return false;
    }

    private static function matchesSingleType(mixed $value, string $type): bool
    {
        return match ($type) {
            'array' => is_array($value),
            'bool' => is_bool($value),
            'float' => is_float($value),
            'int' => is_int($value),
            'list' => is_array($value) && array_is_list($value),
            'mixed' => true,
            'null' => $value === null,
            'object' => is_object($value),
            'scalar' => is_scalar($value),
            'string' => is_string($value),
            default => class_exists($type) && $value instanceof $type,
        };
    }

    private static function typeError(string $field, string $type, mixed $value): string
    {
        return sprintf('field `%s` must be %s, got %s', $field, $type, get_debug_type($value));
    }
}
