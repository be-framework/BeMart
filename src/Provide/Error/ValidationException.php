<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Provide\Error;

use BEAR\Resource\Exception\ExceptionInterface;
use RuntimeException;
use Throwable;

/**
 * Carries per-field request-validation errors as a structured map.
 *
 * Thrown by {@see JsonSchemaRequestExceptionHandler} after grouping
 * BEAR.Resource's structured request-schema errors ({@see \BEAR\Resource\JsonSchema\JsonSchemaErrors}).
 * {@see ExceptionStatusMapper} maps it to a 400, and both the JSON
 * ({@see AppThrowableHandler}) and html ({@see HtmlThrowableHandler}) handlers
 * surface the same `field => list<string>` shape so the wire body stays
 * consistent across contexts — mirroring MyVendor.Cms's validation layer.
 */
final class ValidationException extends RuntimeException implements ExceptionInterface
{
    /** @param array<string, list<string>> $errors field path => human-readable (ja) messages */
    public function __construct(
        public readonly array $errors,
        string $message = 'Validation failed',
        Throwable|null $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
