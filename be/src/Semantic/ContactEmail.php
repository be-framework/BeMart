<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\EmailFormatException;

use function mb_strlen;
use function str_contains;

final class ContactEmail
{
    #[Validate]
    public function validate(string $contactEmail): void
    {
        if (! str_contains($contactEmail, '@') || mb_strlen($contactEmail) > 254 || mb_strlen($contactEmail) < 3) {
            throw new EmailFormatException();
        }
    }
}
