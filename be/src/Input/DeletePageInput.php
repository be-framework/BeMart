<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\PageDeleted;

/**
 * Input for doDeletePage — admin removes a user-created page (Wave 9,
 * idempotent). System pages (pageEditType >= 2) cannot be deleted.
 */
#[Be(PageDeleted::class)]
final readonly class DeletePageInput
{
    /**
     * @psalm-taint-source input $pageId
     */
    public function __construct(
        public string $pageId,
    ) {
    }
}
