<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\NewsUpdated;

/**
 * Input for doUpdateNews — admin edits a news post (Wave 9, idempotent).
 */
#[Be(NewsUpdated::class)]
final readonly class UpdateNewsInput
{
    /**
     * @psalm-taint-source input $newsId
     * @psalm-taint-source input $newsTitle
     * @psalm-taint-source input $newsDescription
     * @psalm-taint-source input $newsUrl
     * @psalm-taint-source input $publishDate
     * @psalm-taint-source input $linkMethod
     */
    public function __construct(
        public string $newsId,
        public string|null $newsTitle = null,
        public string|null $newsDescription = null,
        public string|null $newsUrl = null,
        public string|null $publishDate = null,
        public bool|null $linkMethod = null,
    ) {
    }
}
