<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Madapaja\TwigModule\TwigModule;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Auth\HtmlSessionAdapter;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Override;
use Ray\Di\AbstractModule;
use Ray\WebFormModule\WebFormModule;
use Twig\Environment;

/** HTML presentation modifier. It does not choose SQL/Fake/Test persistence. */
final class HtmlModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        // TwigModule binds RenderInterface -> TwigRenderer.
        $this->override(new TwigModule());
        $this->bind(CustomerSession::class)->to(HtmlSessionAdapter::class);
        $this->bind(AdminSession::class)->to(HtmlAdminSessionAdapter::class);
        $this->bind(Environment::class)->toProvider(HtmlTwigEnvironmentProvider::class);
        $this->install(new WebFormModule());
    }
}
