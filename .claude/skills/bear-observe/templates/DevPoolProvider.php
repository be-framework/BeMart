<?php

declare(strict_types=1);

namespace __NAMESPACE__\Module;

use Ray\Di\ProviderInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

use function dirname;

/**
 * Pools that survive the request boundary, so a second request can be a hit.
 *
 * @implements ProviderInterface<AdapterInterface>
 */
final class DevPoolProvider implements ProviderInterface
{
    public function get(): AdapterInterface
    {
        return new FilesystemAdapter('observe-pool', 0, dirname(__DIR__, 2) . '/var/tmp/observe-pool');
    }
}
