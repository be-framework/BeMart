<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use MyVendor\BeMart\Be\Exception\MailTemplateNotFoundException;
use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final readonly class MailTemplateUpdateResult implements PostQueryInterface
{
    public function __construct(public bool $updated) {}

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $row = $context->rows[0] ?? [];
        $updated = is_array($row) && (int) ($row['updated'] ?? 0) === 1;
        if (! $updated) {
            throw new MailTemplateNotFoundException();
        }

        return new static(true);
    }
}
