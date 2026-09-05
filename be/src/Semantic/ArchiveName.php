<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Uploaded archive file name. The content validation belongs to the
 * file-upload boundary; this semantic registers the domain term.
 */
final class ArchiveName
{
    #[Validate]
    public function validate(string $archiveName): void
    {
    }
}
