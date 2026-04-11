<?php

declare(strict_types=1);

namespace MigrationOrchestrator;

final class SchemaValidator
{
    private const SCHEMA_MAP = [
        'workflow' => 'workflow.schema.json',
        'packet' => 'packet.schema.json',
        'task' => 'task.schema.json',
        'run-state' => 'run-state.schema.json',
        'step-result' => 'step-result.schema.json',
    ];

    public function __construct(private readonly ProjectPaths $paths)
    {
    }

    public function validateFile(string $path, ?string $kind = null): array
    {
        $kind ??= $this->detectKind($path);
        if ($kind === null) {
            return ['Could not determine schema kind. Pass an explicit kind.'];
        }

        $payload = JsonFile::decodeFile($path);
        return $this->validate($kind, $payload);
    }

    public function assertValid(string $kind, array $payload): void
    {
        $errors = $this->validate($kind, $payload);
        if ($errors !== []) {
            throw new \RuntimeException(sprintf(
                'Schema validation failed for %s: %s',
                $kind,
                implode('; ', $errors)
            ));
        }
    }

    public function validate(string $kind, array $payload): array
    {
        $schemaPath = $this->paths->schemaDir() . '/' . (self::SCHEMA_MAP[$kind] ?? '');
        if (!is_file($schemaPath)) {
            throw new \RuntimeException(sprintf('Unknown schema kind: %s', $kind));
        }

        $schema = JsonFile::decodeFile($schemaPath);
        $errors = [];
        $this->validateValue($payload, $schema, '$', $errors);
        return $errors;
    }

    public function detectKind(string $path): ?string
    {
        $resolved = realpath($path);
        $normalized = str_replace('\\', '/', $resolved !== false ? $resolved : $path);
        $basename = basename($normalized);

        if ($basename === 'state.json') {
            return 'run-state';
        }

        if (str_ends_with($basename, '.step-result.json')) {
            return 'step-result';
        }

        if (str_contains($normalized, '/.migrate/workflows/')) {
            return 'workflow';
        }

        if (str_contains($normalized, '/.migrate/packets/')) {
            return 'packet';
        }

        if (str_contains($normalized, '/.migrate/tasks/') || str_contains($normalized, '/.migrate/examples/tasks/')) {
            return 'task';
        }

        return null;
    }

    private function validateValue(mixed $value, array $schema, string $path, array &$errors): void
    {
        if (isset($schema['type']) && !$this->matchesType($value, (string) $schema['type'])) {
            $errors[] = sprintf('%s expected %s', $path, $schema['type']);
            return;
        }

        if (array_key_exists('enum', $schema) && is_array($schema['enum']) && !$this->matchesEnum($value, $schema['enum'])) {
            $errors[] = sprintf('%s expected one of [%s]', $path, implode(', ', $schema['enum']));
        }

        if (isset($schema['minimum']) && (is_int($value) || is_float($value)) && $value < $schema['minimum']) {
            $errors[] = sprintf('%s must be >= %s', $path, (string) $schema['minimum']);
        }

        if (isset($schema['minItems']) && is_array($value) && array_is_list($value) && count($value) < $schema['minItems']) {
            $errors[] = sprintf('%s must contain at least %d items', $path, $schema['minItems']);
        }

        if (($schema['type'] ?? null) === 'object' && is_array($value)) {
            $properties = $schema['properties'] ?? [];
            $required = $schema['required'] ?? [];

            foreach ($required as $requiredProperty) {
                if (!array_key_exists($requiredProperty, $value)) {
                    $errors[] = sprintf('%s.%s is required', $path, $requiredProperty);
                }
            }

            foreach ($value as $property => $propertyValue) {
                if (isset($properties[$property]) && is_array($properties[$property])) {
                    $this->validateValue($propertyValue, $properties[$property], $path . '.' . $property, $errors);
                    continue;
                }

                if (array_key_exists('additionalProperties', $schema)) {
                    $additional = $schema['additionalProperties'];
                    if ($additional === false) {
                        $errors[] = sprintf('%s.%s is not allowed', $path, $property);
                        continue;
                    }

                    if (is_array($additional)) {
                        $this->validateValue($propertyValue, $additional, $path . '.' . $property, $errors);
                    }
                }
            }
        }

        if (($schema['type'] ?? null) === 'array' && is_array($value) && array_is_list($value) && isset($schema['items']) && is_array($schema['items'])) {
            foreach ($value as $index => $item) {
                $this->validateValue($item, $schema['items'], sprintf('%s[%d]', $path, $index), $errors);
            }
        }
    }

    private function matchesEnum(mixed $value, array $enum): bool
    {
        foreach ($enum as $candidate) {
            if ($candidate === $value) {
                return true;
            }
        }

        return false;
    }

    private function matchesType(mixed $value, string $expectedType): bool
    {
        return match ($expectedType) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'array' => is_array($value) && ($value === [] || array_is_list($value)),
            'object' => is_array($value) && ($value === [] || !array_is_list($value)),
            default => false,
        };
    }
}
