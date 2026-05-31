<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use function strlen;

/** A downloadable template archive (zip body + headers). */
final readonly class TemplateArchive
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
