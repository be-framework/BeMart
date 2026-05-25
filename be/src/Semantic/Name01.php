<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\Name01FormatException;

use function mb_strlen;

/**
 * Family name — EC-CUBE 4.3 dtb_customer.name01.
 */
final class Name01
{
    #[Validate]
    public function validate(string $name01): void
    {
        $length = mb_strlen($name01);
        if ($length < 1 || $length > 50) {
            throw new Name01FormatException();
        }
    }
}
