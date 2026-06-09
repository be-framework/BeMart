<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Dev\Html\LinkHeaderModule;
use BEAR\Package\Provide\Representation\RouterReverseLinker;
use BEAR\Resource\ReverseLinkerInterface;
use Madapaja\TwigModule\TwigModule;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Override;
use Ray\Di\AbstractModule;
use Ray\WebFormModule\WebFormModule;
use Twig\Environment;

/** HTML presentation modifier. It does not choose SQL/Fake/Test persistence. */
final class HtmlModule extends AbstractModule
{
    /** @param array<string, mixed> $twigOptions */
    public function __construct(
        AbstractModule|null $module = null,
        private readonly array $twigOptions = [],
    ) {
        parent::__construct($module);
    }

    #[Override]
    protected function configure(): void
    {
        $this->override(new LinkHeaderModule(new TwigModule(options: $this->twigOptions)));
        $this->bind(ReverseLinkerInterface::class)->to(RouterReverseLinker::class);
        $this->bind(AdminSession::class)->to(HtmlAdminSessionAdapter::class);
        $this->bind(Environment::class)->toProvider(HtmlTwigEnvironmentProvider::class);
        $this->install(new WebFormModule());
    }
}
