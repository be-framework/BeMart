<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * CMS free page — projection of EC-CUBE dtb_page (Wave 9 CMS slice).
 *
 * `pageEditType` encodes EC-CUBE's edit-level enum:
 *   0 = EDIT_TYPE_USER          (admin-created free page, fully editable)
 *   1 = EDIT_TYPE_PREVIEW       (preview state)
 *   2 = EDIT_TYPE_DEFAULT       (system page, structure locked, no delete)
 *   3 = EDIT_TYPE_DEFAULT_CONFIRM (system page, body editable, no delete)
 *
 * The first iteration projects the table as a flat list — full
 * sitemap / Twig regeneration semantics are deferred to Phase 2.
 */
final readonly class PageEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public function __construct(
        public string $pageId,
        public string $pageName,
        public string $pageUrl,
        public string $pageFileName,
        public int $pageEditType,
    ) {
    }
}
