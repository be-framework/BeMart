<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\Exception\SemanticVariableException;

/**
 * @mixin ResourceObject
 */
trait ResourceErrorResponder
{
    /** @param array<string, mixed> $context */
    private function errorResponse(int $code, string $message, array $context = []): static
    {
        $this->code = $code;
        $this->body = ErrorResponseBody::fromMessage($message, $context);

        return $this;
    }

    /** @param array<string, mixed> $context */
    private function semanticBadRequest(
        SemanticVariableException $exception,
        array $context = [],
        string $fallback = ErrorResponseBody::DEFAULT_VALIDATION_MESSAGE,
    ): static {
        $this->code = Code::BAD_REQUEST;
        $this->body = ErrorResponseBody::fromSemanticVariable($exception, $context, $fallback);

        return $this;
    }
}
