<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Override;
use Ray\Di\AbstractModule;
use Ray\MediaQuery\DbQueryConfig;
use Ray\MediaQuery\MediaQueryBaseModule;
use Ray\MediaQuery\MediaQueryDbModule;
use Ray\MediaQuery\Queries;

use function dirname;

/** Registers every public #[DbQuery] interface as a Ray.MediaQuery proxy. */
final class MediaQueryProxyModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $root = dirname(__DIR__, 2);
        $queries = Queries::fromClasses(MediaQueryRuntimeModule::queryClasses());

        /**
         * @psalm-suppress InternalClass
         * @psalm-suppress InternalMethod
         */
        $this->install(new MediaQueryBaseModule($queries));
        /**
         * @psalm-suppress InternalClass
         * @psalm-suppress InternalMethod
         */
        $this->install(new MediaQueryDbModule(new DbQueryConfig($root . '/sql/media-query')));
    }
}
