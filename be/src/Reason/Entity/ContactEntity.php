<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Customer inquiry — projection of EC-CUBE dtb_contact for Pilot 15.
 * Held only long enough for the mail bodies to be rendered; not
 * persisted in the Be-Framework layer (Phase 2 may add `dtb_contact`
 * INSERT in the same Final).
 */
final readonly class ContactEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public function __construct(
        public string $contactName01,
        public string $contactName02,
        public string $contactEmail,
        public string $contactContents,
    ) {
    }
}
