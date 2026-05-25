<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\Name02FormatException;

use function mb_strlen;

final class ContactName02
{
    #[Validate]
    public function validate(string $contactName02): void
    {
        $length = mb_strlen($contactName02);
        if ($length < 1 || $length > 50) {
            throw new Name02FormatException();
        }
    }
}
