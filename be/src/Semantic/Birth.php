<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use DateTimeImmutable;
use MyVendor\BeMart\Be\Exception\BirthFormatException;
use Throwable;

use function preg_match;

/**
 * Birth date — ISO 8601 date string (YYYY-MM-DD). The Final keeps the
 * raw string; consumers parse to DateTimeImmutable when they need a
 * date object.
 */
final class Birth
{
    #[Validate]
    public function validate(string|null $birth): void
    {
        if ($birth === null || $birth === '') {
            return;
        }

        if (preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $birth) !== 1) {
            throw new BirthFormatException();
        }

        try {
            new DateTimeImmutable($birth);
        } catch (Throwable) {
            throw new BirthFormatException();
        }
    }
}
