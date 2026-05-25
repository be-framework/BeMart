<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\PageUpdated;

/**
 * Input for doUpdatePage — admin edits a CMS page (Wave 9, idempotent).
 *
 * Merge semantics: nullable fields preserve existing values.
 */
#[Be(PageUpdated::class)]
final readonly class UpdatePageInput
{
    /**
     * @psalm-taint-source input $pageId
     * @psalm-taint-source input $pageName
     * @psalm-taint-source input $pageUrl
     * @psalm-taint-source input $pageFileName
     */
    public function __construct(
        public string $pageId,
        public string|null $pageName = null,
        public string|null $pageUrl = null,
        public string|null $pageFileName = null,
    ) {
    }
}
