<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Page template filename — EC-CUBE 4.3 dtb_page.file_name (Wave 9).
 * Type assertion only.
 */
final class PageFileName
{
    #[Validate]
    public function validate(string|null $pageFileName): void
    {
        // Type assertion only.
    }
}
