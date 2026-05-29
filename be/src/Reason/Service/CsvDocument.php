<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use function strlen;

/**
 * A generated CSV download (EC-CUBE-compatible body + headers). Mirrors
 * {@see OrderPdfDocument} for the CSV export boundary.
 */
final readonly class CsvDocument
{
    public int $size;

    public function __construct(
        public string $content,
        public string $fileName,
        public string $contentDisposition,
    ) {
        $this->size = strlen($content);
    }
}
