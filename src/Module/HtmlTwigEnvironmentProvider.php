<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Aura\Router\RouterContainer;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
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
 * `price` / `asset` / `url` / `path` helpers the ported EC-CUBE templates
 * call), and serves it as the unqualified Environment so TwigRenderer
 * gets an extension-equipped instance.
 *
 * The extension receives the same Aura.Router container the application
 * router uses, so the `url()` / `path()` hrefs it emits are URLs the router
 * can resolve.
 *
 * @implements ProviderInterface<Environment>
 */
final class HtmlTwigEnvironmentProvider implements ProviderInterface
{
    public function __construct(
        #[Named('original')]
        private readonly Environment $twig,
        RouterContainer $routes,
        private readonly CsrfToken $csrf,
    ) {
        // HTML migration work edits Twig templates and static assets while the
        // local server is running. Madapaja.TwigModule caches compiled
        // templates by default, so without auto_reload the browser can keep
        // rendering stale markup/CSS links until var/tmp is manually cleared.
        $twig->enableAutoReload();

        if (! $twig->hasExtension(BeMartTwigExtension::class)) {
            $twig->addExtension(new BeMartTwigExtension($routes, $this->csrf));
        }
    }

    #[Override]
    public function get(): Environment
    {
        return $this->twig;
    }
}
