<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\TagCreated;

/**
 * Input for doCreateTag (Wave 9).
 */
#[Be(TagCreated::class)]
final readonly class CreateTagInput
{
    /**
     * @psalm-taint-source input $tagName
     */
    public function __construct(
        public string $tagName,
    ) {
    }
}
