<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Resource;

use Be\Framework\Exception\SemanticVariableException;

/**
 * Canonical ResourceObject error body shape.
 *
 * Every error body must carry a human-readable `message`. Resource-specific
 * context, such as `productCode`, remains top-level so existing callers and
 * templates can keep reading it without another wrapper layer.
 */
final class ErrorResponseBody
{
    public const string DEFAULT_VALIDATION_MESSAGE = 'Invalid input.';

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public static function fromMessage(string $message, array $context = []): array
    {
        return ['message' => $message] + $context;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public static function fromSemanticVariable(
        SemanticVariableException $exception,
        array $context = [],
        string $fallback = self::DEFAULT_VALIDATION_MESSAGE,
    ): array {
        return self::fromMessage(self::semanticMessage($exception, $fallback), $context);
    }

    public static function semanticMessage(
        SemanticVariableException $exception,
        string $fallback = self::DEFAULT_VALIDATION_MESSAGE,
    ): string {
        $messages = $exception->getErrors()->getMessages('ja');

        return $messages[0] ?? $fallback;
    }
}
