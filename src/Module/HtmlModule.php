<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Dev\Html\LinkHeaderModule;
use BEAR\Resource\ReverseLinkerInterface;
use Madapaja\TwigModule\TwigModule;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Auth\HtmlSessionAdapter;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Support\Router\ResourceUriReverseLinker;
use Override;
use Ray\Di\AbstractModule;
use Ray\WebFormModule\WebFormModule;
use Twig\Environment;

/** HTML presentation modifier. It does not choose SQL/Fake/Test persistence. */
final class HtmlModule extends AbstractModule
{
    /** @param array<string, mixed> $twigOptions */
    public function __construct(
        private readonly array $twigOptions = [],
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        // LinkHeaderModule wraps TwigRenderer and exposes #[Link] as HTTP Link headers.
        $this->override(new LinkHeaderModule(new TwigModule(options: $this->twigOptions)));
        $this->bind(ReverseLinkerInterface::class)->to(ResourceUriReverseLinker::class);
        $this->bind(CustomerSession::class)->to(HtmlSessionAdapter::class);
        $this->bind(AdminSession::class)->to(HtmlAdminSessionAdapter::class);
        $this->bind(Environment::class)->toProvider(HtmlTwigEnvironmentProvider::class);
        $this->install(new WebFormModule());
    }
}
