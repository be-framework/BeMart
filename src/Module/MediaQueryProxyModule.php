<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Override;
use Ray\Di\AbstractModule;
use Ray\MediaQuery\DbQueryConfig;
use Ray\MediaQuery\MediaQueryModule;

use function dirname;

/** Registers every public #[DbQuery] interface as a Ray.MediaQuery proxy. */
final class MediaQueryProxyModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $root = dirname(__DIR__, 2);
        /**
         * A plain MediaQuery directory scan would discover non-query helper
         * classes in be/src/Reason/Query. Use MediaQueryModule with a
         * #[DbQuery]-filtered Queries object to exclude them.
         *
         * @psalm-suppress InternalClass
         * @psalm-suppress InternalMethod
         */
        $this->install(new MediaQueryModule(
            MediaQueryQueries::fromAppRoot($root),
            [new DbQueryConfig($root . '/var/sql')],
        ));
    }
}
