<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Uploaded archive size in bytes. The upload boundary enforces limits;
 * this semantic registers the value passed into the install transition.
 */
final class ArchiveSize
{
    #[Validate]
    public function validate(int $archiveSize): void
    {
    }
}
