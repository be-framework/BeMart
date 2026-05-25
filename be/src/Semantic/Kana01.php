<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\Kana01FormatException;

use function mb_strlen;
use function preg_match;

/**
 * Family-name kana — EC-CUBE 4.3 dtb_customer.kana01. Full-width
 * katakana only (HW input is auto-converted upstream). Optional.
 */
final class Kana01
{
    #[Validate]
    public function validate(string|null $kana01): void
    {
        if ($kana01 === null || $kana01 === '') {
            return;
        }

        if (mb_strlen($kana01) > 50 || preg_match('/\A[\x{30A0}-\x{30FF}ー]+\z/u', $kana01) !== 1) {
            throw new Kana01FormatException();
        }
    }
}
