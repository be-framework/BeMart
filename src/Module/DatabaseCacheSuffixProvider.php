<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Override;
use Ray\Di\ProviderInterface;

/**
 * Provides the per-database cache suffix (see {@see DatabaseUrl::cacheSuffix()})
 * to DI-managed services that namespace runtime files by database.
 *
 * Bound as a provider rather than a baked `toInstance` value so DATABASE_URL is
 * read when the object graph is built, not frozen into the compiled container —
 * otherwise a context whose DI cache was compiled against one database (e.g.
 * eccubedb) would keep that suffix when run against another (eccubedb_test).
 *
 * @implements ProviderInterface<string>
 */
final class DatabaseCacheSuffixProvider implements ProviderInterface
{
    #[Override]
    public function get(): string
    {
        return DatabaseUrl::cacheSuffix();
    }
}
