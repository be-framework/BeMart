<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\Name02FormatException;

use function mb_strlen;

/**
 * Given name — EC-CUBE 4.3 dtb_customer.name02.
 */
final class Name02
{
    #[Validate]
    public function validate(string|null $name02): void
    {
        if ($name02 === null) {
            return;
        }

        $length = mb_strlen($name02);
        if ($length < 1 || $length > 50) {
            throw new Name02FormatException();
        }
    }
}
