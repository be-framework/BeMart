<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Provide\Transfer;

use Override;

use function str_contains;

final class ApiDownloadContentTypePolicy implements DownloadContentTypePolicyInterface
{
    #[Override]
    public function __invoke(string $contentType): bool
    {
        return str_contains($contentType, 'application/zip')
            || str_contains($contentType, 'application/pdf')
            || str_contains($contentType, 'application/octet-stream');
    }
}
