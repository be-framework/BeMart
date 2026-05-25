<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\NewsDeleted;

/**
 * Input for doDeleteNews — admin removes a news post (Wave 9, idempotent).
 */
#[Be(NewsDeleted::class)]
final readonly class DeleteNewsInput
{
    /**
     * @psalm-taint-source input $newsId
     */
    public function __construct(
        public string $newsId,
    ) {
    }
}
