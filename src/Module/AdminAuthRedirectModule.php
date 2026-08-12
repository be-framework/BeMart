<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Resource\RenderInterface;
use MyVendor\BeMart\Provide\Render\AdminAuthRedirectRenderer;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

/**
 * Decorates the Twig renderer with the admin-firewall redirect behaviour.
 * Same wrapper shape as BEAR.Dev's LinkHeaderModule: the inner renderer
 * stays available under the 'twig' qualifier.
 */
final class AdminAuthRedirectModule extends AbstractModule
{
    public function __construct(AbstractModule $module)
    {
        parent::__construct($module);
    }

    #[Override]
    protected function configure(): void
    {
        $this->rename(RenderInterface::class, 'twig');
        $this->bind(RenderInterface::class)->to(AdminAuthRedirectRenderer::class)->in(Scope::SINGLETON);
    }
}
