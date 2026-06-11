<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Provide\Transfer;

interface DownloadContentTypePolicyInterface
{
    public function __invoke(string $contentType): bool;
}
