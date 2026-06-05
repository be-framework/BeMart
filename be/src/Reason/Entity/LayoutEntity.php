<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Layout — projection of EC-CUBE dtb_layout (Wave 9).
 *
 * `deviceType` mirrors EC-CUBE's device master enum: 10=PC, 2=Mobile.
 * Block-placement detail (header / contents_top / side_left / ...) is
 * deferred to Phase 2 — the first iteration treats layouts as opaque
 * named containers.
 */
final readonly class LayoutEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public int $deviceType;

    public function __construct(
        public string $layoutId,
        public string $layoutName,
        int|string $deviceType,
    ) {
        $this->deviceType = (int) $deviceType;
    }
}
