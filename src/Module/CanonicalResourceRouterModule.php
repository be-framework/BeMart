<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\Provide\Router\RouterCollectionProvider;
use BEAR\Sunday\Extension\Router\RouterInterface;
use MyVendor\BeMart\Support\Router\CanonicalResourceRouter;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class CanonicalResourceRouterModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(RouterInterface::class)->annotatedWith('primary_router')->to(CanonicalResourceRouter::class);
        $this->bind(RouterInterface::class)->toProvider(RouterCollectionProvider::class)->in(Scope::SINGLETON);
    }
}
