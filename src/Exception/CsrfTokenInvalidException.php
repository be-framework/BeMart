<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Exception;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\BadRequestException;
use Throwable;

final class CsrfTokenInvalidException extends BadRequestException
{
    public function __construct(
        string $message = 'Invalid or missing CSRF token.',
        Throwable|null $previous = null,
    ) {
        parent::__construct($message, Code::FORBIDDEN, $previous);
    }
}
