<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Provide\Transfer;

use Override;

use function str_contains;

final class HtmlDownloadContentTypePolicy implements DownloadContentTypePolicyInterface
{
    public function __construct(private readonly ApiDownloadContentTypePolicy $apiPolicy)
    {
    }

    #[Override]
    public function __invoke(string $contentType): bool
    {
        return str_contains($contentType, 'text/csv')
            || str_contains($contentType, 'application/json')
            || ($this->apiPolicy)($contentType);
    }
}
