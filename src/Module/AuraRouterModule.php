<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Aura\Router\RouterContainer;
use BEAR\Package\Provide\Router\RouterCollectionProvider;
use BEAR\Sunday\Extension\Router\RouterInterface;
use MyVendor\BeMart\Support\Router\AuraRouter;
use MyVendor\BeMart\Support\Router\RouterContainerProvider;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class AuraRouterModule extends AbstractModule
{
    public function __construct(
        private readonly string $routerFile,
        AbstractModule|null $module = null,
    ) {
        parent::__construct($module);
    }

    #[Override]
    protected function configure(): void
    {
        $this->bind()->annotatedWith('aura_router_file')->toInstance($this->routerFile);
        $this->bind(RouterContainer::class)->toProvider(RouterContainerProvider::class)->in(Scope::SINGLETON);
        $this->bind(RouterInterface::class)->annotatedWith('primary_router')->to(AuraRouter::class);
        $this->bind(RouterInterface::class)->toProvider(RouterCollectionProvider::class)->in(Scope::SINGLETON);
    }
}
