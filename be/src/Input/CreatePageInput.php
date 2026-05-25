<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\PageCreated;

/**
 * Input for doCreatePage — admin creates a new free CMS page (Wave 9).
 *
 *   CreatePageInput → PageCreated (Direct, admin AUTHZ)
 *
 * New pages are always EDIT_TYPE_USER (0) — system pages are only
 * created by EC-CUBE installer / migrations.
 */
#[Be(PageCreated::class)]
final readonly class CreatePageInput
{
    /**
     * @psalm-taint-source input $pageName
     * @psalm-taint-source input $pageUrl
     * @psalm-taint-source input $pageFileName
     */
    public function __construct(
        public string $pageName,
        public string $pageUrl,
        public string $pageFileName,
    ) {
    }
}
