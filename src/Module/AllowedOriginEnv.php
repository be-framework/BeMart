<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use function getenv;

/**
 * The canonical browser origin BEAR.Csrf's same-origin gate compares against.
 *
 * Unset means "no origin to compare against" — the right answer for CLI and
 * local development, where {@see AppModule} runs the token gate alone.
 * {@see ProdModule} refuses to boot in that state instead.
 */
final readonly class AllowedOriginEnv
{
    public const NAME = 'BEMART_ALLOWED_ORIGIN';

    public static function value(): string|null
    {
        $origin = getenv(self::NAME);

        return $origin === false || $origin === '' ? null : $origin;
    }
}
