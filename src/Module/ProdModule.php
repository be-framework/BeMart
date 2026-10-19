<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\Context\ProdModule as PackageProdModule;
use Override;
use Ray\Di\AbstractModule;

/**
 * Production context: BEAR.Package's ProdModule, behind one boot-time check.
 *
 * {@see AppModule} picks between the two CsrfModule constructors from
 * {@see AllowedOriginEnv}; in development the absent case is correct. In
 * production the same absence would stand the same-origin gate down for every
 * request, silently, so it is an error here rather than a default.
 */
final class ProdModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        if (AllowedOriginEnv::value() === null) {
            throw new MissingAllowedOriginException(AllowedOriginEnv::NAME);
        }

        $this->install(new PackageProdModule());
    }
}
