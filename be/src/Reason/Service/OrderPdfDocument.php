<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use function strlen;

final readonly class OrderPdfDocument
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
