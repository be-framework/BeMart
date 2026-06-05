<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * CMS UI block — projection of EC-CUBE dtb_block (Wave 9).
 *
 * `blockDeletable` mirrors EC-CUBE's "system standard block" flag —
 * built-in blocks (header, footer, ...) cannot be deleted from the
 * admin UI.
 */
final readonly class BlockEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public bool $blockDeletable;

    public function __construct(
        public string $blockId,
        public string $blockName,
        public string $blockFileName,
        bool|int|string $blockDeletable,
    ) {
        $this->blockDeletable = (bool) $blockDeletable;
    }
}
