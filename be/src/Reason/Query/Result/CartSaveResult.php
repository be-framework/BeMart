<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;
use RuntimeException;

final class CartSaveResult implements PostQueryInterface
{
    public function __construct(private readonly bool $saved) {}

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $row = $context->rows[0] ?? [];
        $missing = is_array($row) ? (string) ($row['missing_codes'] ?? '') : '';
        if ($missing !== '') {
            throw new RuntimeException(sprintf('CartCommand: unknown productCode "%s".', $missing));
        }

        return new static(true);
    }

    public function saved(): bool
    {
        return $this->saved;
    }
}
