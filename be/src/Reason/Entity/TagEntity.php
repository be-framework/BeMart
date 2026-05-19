<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Product tag — projection of EC-CUBE dtb_tag (Wave 9 catalog slice).
 *
 * Tags are flat free-text labels attached to products for search /
 * filtering. ALPS exposes only list / create / delete affordances —
 * no rename.
 */
final readonly class TagEntity
{
    public function __construct(
        public string $tagId,
        public string $tagName,
    ) {
    }
}
