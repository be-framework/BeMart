<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\TagDeleted;

/**
 * Input for doDeleteTag (Wave 9, idempotent).
 */
#[Be(TagDeleted::class)]
final readonly class DeleteTagInput
{
    /**
     * @psalm-taint-source input $tagId
     */
    public function __construct(
        public string $tagId,
    ) {
    }
}
