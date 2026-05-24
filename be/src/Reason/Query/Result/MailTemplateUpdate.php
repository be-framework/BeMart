<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use MyVendor\BeMart\Be\Exception\MailTemplateNotFoundException;
use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final readonly class MailTemplateUpdate implements PostQueryInterface
{
    public bool $updated;

    /** @param bool|array<int, self|array<string, mixed>> $updated */
    public function __construct(bool|array $updated)
    {
        if (is_array($updated)) {
            $row = $updated[0] ?? null;
            $updated = $row instanceof self ? $row->updated : $updated !== [];
        }

        if (! $updated) {
            throw new MailTemplateNotFoundException();
        }

        $this->updated = true;
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $row = $context->rows[0] ?? [];
        $updated = is_array($row) && (int) ($row['updated'] ?? 0) === 1;

        return new static($updated);
    }
}
