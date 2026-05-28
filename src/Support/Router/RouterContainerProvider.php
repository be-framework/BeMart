<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Router;

use Aura\Router\Map;
use Aura\Router\RouterContainer;
use BEAR\AppMeta\AbstractAppMeta;
use LogicException;
use Override;
use Ray\Di\Di\Named;
use Ray\Di\ProviderInterface;

use function file_exists;
use function is_callable;
use function sprintf;

/** @implements ProviderInterface<RouterContainer> */
final class RouterContainerProvider implements ProviderInterface
{
    private readonly RouterContainer $routerContainer;

    public function __construct(
        AbstractAppMeta $appMeta,
        #[Named('aura_router_file')]
        string $routerFile = '',
    ) {
        $routerFile = $routerFile === '' ? $appMeta->appDir . '/config/aura-routes.php' : $routerFile;
        if (! file_exists($routerFile)) {
            throw new LogicException(sprintf('Aura router file not found: %s', $routerFile));
        }

        /** @var mixed $routes */
        $routes = require $routerFile;
        if (! is_callable($routes)) {
            throw new LogicException(sprintf('Aura router file must return a callable: %s', $routerFile));
        }

        $this->routerContainer = new RouterContainer();
        /** @var callable(Map): null $routes */
        $this->routerContainer->setMapBuilder($routes);
    }

    #[Override]
    public function get(): RouterContainer
    {
        return $this->routerContainer;
    }
}
