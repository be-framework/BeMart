<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\ContactContentsFormatException;

use function mb_strlen;

/**
 * Contact-form body — 1..2000 chars. EC-CUBE doesn't currently
 * enforce a max, but unbounded HTML body submissions are an abuse
 * surface, so we cap at 2000 (about a long paragraph).
 */
final class ContactContents
{
    #[Validate]
    public function validate(string $contactContents): void
    {
        $length = mb_strlen($contactContents);
        if ($length < 1 || $length > 2000) {
            throw new ContactContentsFormatException();
        }
    }
}
