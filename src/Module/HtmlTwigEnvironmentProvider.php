<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Override;
use Ray\Di\Di\Named;
use Ray\Di\ProviderInterface;
use Twig\Environment;

/**
 * Decorates the Twig Environment with BeMart's EC-CUBE-port helpers.
 *
 * TwigModule binds two Environments: the plain one (`RenderInterface`
 * pulls), and an `@original`-annotated copy. This provider takes the
 * `@original` Environment, registers {@see BeMartTwigExtension} (the
 * `price` / `asset` / CSRF helpers the ported EC-CUBE templates call),
 * and serves it as the unqualified Environment so TwigRenderer
 * gets an extension-equipped instance.
 *
 * @implements ProviderInterface<Environment>
 */
final class HtmlTwigEnvironmentProvider implements ProviderInterface
{
    public function __construct(
        #[Named('original')]
        private readonly Environment $twig,
    ) {
        if (! $twig->hasExtension(BeMartTwigExtension::class)) {
            $twig->addExtension(new BeMartTwigExtension());
        }
    }

    #[Override]
    public function get(): Environment
    {
        return $this->twig;
    }
}
