<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\NewsCreated;

/**
 * Input for doCreateNews — admin posts a new news entry (Wave 9).
 */
#[Be(NewsCreated::class)]
final readonly class CreateNewsInput
{
    /**
     * @psalm-taint-source input $newsTitle
     * @psalm-taint-source input $newsDescription
     * @psalm-taint-source input $newsUrl
     * @psalm-taint-source input $publishDate
     * @psalm-taint-source input $linkMethod
     */
    public function __construct(
        public string $newsTitle,
        public string $publishDate,
        public string|null $newsDescription = null,
        public string|null $newsUrl = null,
        public bool $linkMethod = false,
    ) {
    }
}
