<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;
use RuntimeException;

final readonly class SavedCart implements PostQueryInterface
{
    public bool $saved;

    /** @param bool|array<int, self|array<string, mixed>> $saved */
    public function __construct(bool|array $saved = true)
    {
        if (is_array($saved)) {
            $row = $saved[0] ?? null;
            $saved = $row instanceof self ? $row->saved : true;
        }

        $this->saved = $saved;
    }

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
}
