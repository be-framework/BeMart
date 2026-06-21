<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Provide\Error;

use BEAR\Resource\Exception\JsonSchemaException;
use BEAR\Resource\JsonSchemaRequestExceptionHandlerInterface;
use BEAR\Resource\ResourceObject;
use Override;

use function file_get_contents;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function preg_split;
use function sprintf;

/**
 * Surface request-parameter (form) validation failures as a field-keyed
 * {@see ValidationException}, instead of the default null handler's opaque
 * re-throw of the raw {@see JsonSchemaException}.
 *
 * Since `bear/resource` 1.33.0 the interceptor always delivers a
 * {@see \BEAR\Resource\Exception\JsonSchemaRequestException} carrying the
 * structured {@see \BEAR\Resource\JsonSchema\JsonSchemaErrors} collection, so
 * the per-field detail is read directly from `$e->getErrors()` — no
 * re-validation. Each failing property maps to its Japanese schema `title`
 * (the field's human label); a schema-side `errorMessage` (ajv-errors), when
 * present, wins as the message via {@see \BEAR\Resource\JsonSchema\JsonSchemaError::$isCustomMessage}.
 *
 * Bound over {@see \BEAR\Resource\JsonSchemaRequestExceptionNullHandler} in
 * {@see \MyVendor\BeMart\Module\AppModule}. Mirrors MyVendor.Cms's
 * `JsonSchemaRequestExceptionHandler` and the BEAR.Sunday validation manual.
 */
final class JsonSchemaRequestExceptionHandler implements JsonSchemaRequestExceptionHandlerInterface
{
    /**
     * @param array<string, mixed> $arguments
     *
     * @inheritDoc
     */
    #[Override]
    public function handleRequestException(
        array $arguments,
        ResourceObject $ro,
        JsonSchemaException $e,
        string $schemaFile,
    ): never {
        $jsonErrors = $e->getErrors();
        // Defence against an exception raised outside the normal interceptor
        // path (no structured errors): preserve the original signal rather than
        // surface an empty-shape ValidationException.
        if (! $jsonErrors->hasErrors()) {
            throw $e;
        }

        $titles = $this->fieldTitles($schemaFile);
        $errors = [];
        $labels = [];
        foreach ($jsonErrors as $error) {
            $label = $this->labelFor($error->property, $titles);
            $message = $error->isCustomMessage ? $error->message : sprintf('%sを確認してください', $label);
            $errors[$error->property === '' ? '_root' : $error->property][] = $message;
            if (! in_array($label, $labels, true)) {
                $labels[] = $label;
            }
        }

        throw new ValidationException(
            $errors,
            sprintf('入力内容を確認してください: %s', implode('、', $labels)),
            $e,
        );
    }

    /**
     * Japanese label for a failing property: its own schema `title`, else — for
     * a nested/array path like `orderItems[0].productCode` that has no top-level
     * title — the title of its root segment (`orderItems`), else the raw path.
     * Keeps the label human (ja) for item-level failures and collapses the N
     * errors of an invalid array to one bounded label in the summary line.
     *
     * @param array<string, string> $titles
     */
    private function labelFor(string $property, array $titles): string
    {
        if ($property === '') {
            return '入力';
        }

        if (isset($titles[$property])) {
            return $titles[$property];
        }

        $segments = preg_split('/[.\[]/', $property);
        $root = is_array($segments) && $segments !== [] ? $segments[0] : $property;

        return $titles[$root] ?? $property;
    }

    /**
     * property name => Japanese title, from the request schema's `properties`.
     *
     * @return array<string, string>
     */
    private function fieldTitles(string $schemaFile): array
    {
        $raw = file_get_contents($schemaFile);
        if ($raw === false) {
            return [];
        }

        // Best-effort enrichment only: a decode failure must never demote the
        // intended 400 to a 500, so decode without JSON_THROW_ON_ERROR (mirrors
        // bear/resource's own schema-message lookup) and bail to no titles.
        $schema = json_decode($raw, true);
        if (! is_array($schema)) {
            return [];
        }

        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
        $titles = [];
        foreach ($properties as $name => $definition) {
            if (is_string($name) && is_array($definition) && is_string($definition['title'] ?? null)) {
                $titles[$name] = $definition['title'];
            }
        }

        return $titles;
    }
}
