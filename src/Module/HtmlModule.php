<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Dev\Html\LinkHeaderModule;
use BEAR\Package\Provide\Representation\RouterReverseLinker;
use BEAR\Resource\ReverseLinkerInterface;
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
    /** @param array<string, mixed> $twigOptions */
    public function __construct(
        AbstractModule|array|null $moduleOrTwigOptions = null,
    ) {
        $this->twigOptions = $moduleOrTwigOptions instanceof AbstractModule || $moduleOrTwigOptions === null
            ? []
            : $moduleOrTwigOptions;
        parent::__construct($moduleOrTwigOptions instanceof AbstractModule ? $moduleOrTwigOptions : null);
    }

    /** @var array<string, mixed> */
    private readonly array $twigOptions;

    #[Override]
    protected function configure(): void
    {
        // RenderInterface -> LinkHeaderRenderer -> TwigRenderer.
        $this->override(new LinkHeaderModule(new TwigModule(options: $this->twigOptions)));
        $this->bind(ReverseLinkerInterface::class)->to(RouterReverseLinker::class);
        $this->bind(CustomerSession::class)->to(HtmlSessionAdapter::class);
        $this->bind(AdminSession::class)->to(HtmlAdminSessionAdapter::class);
        $this->bind(Environment::class)->toProvider(HtmlTwigEnvironmentProvider::class);
        $this->install(new WebFormModule());
    }
}
