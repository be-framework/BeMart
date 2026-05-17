<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\Kana02FormatException;

use function mb_strlen;
use function preg_match;

/**
 * Given-name kana — EC-CUBE 4.3 dtb_customer.kana02. Optional.
 */
final class Kana02
{
    #[Validate]
    public function validate(string|null $kana02): void
    {
        if ($kana02 === null || $kana02 === '') {
            return;
        }

        if (mb_strlen($kana02) > 50 || preg_match('/\A[\x{30A0}-\x{30FF}ー]+\z/u', $kana02) !== 1) {
            throw new Kana02FormatException();
        }
    }
}
