<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Resource;

use InvalidArgumentException;

use function implode;
use function sprintf;

final class ResourceSchemaViolationException extends InvalidArgumentException
{
    /** @param list<string> $errors */
    public function __construct(string $schemaName, array $errors)
    {
        parent::__construct(sprintf(
            "%s schema failed:\n- %s",
            $schemaName,
            implode("\n- ", $errors),
        ));
    }
}
