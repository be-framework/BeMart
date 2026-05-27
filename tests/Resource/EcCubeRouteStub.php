<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use MyVendor\BeMart\Module\BeMartTwigExtension;
use Twig\Environment;
use Twig\TwigFunction;


/**
 * Registers `url()` / `path()` on a render-diff test's EC-CUBE-side Twig
 * Environment.
 *
 * ## Why this is shared infrastructure, not an inline stub
 *
 * A render-diff test renders EC-CUBE's real template and BeMart's port and
 * asserts they agree. For the diff to be honest, helpers EC-CUBE's
 * template calls must be exercised IDENTICALLY on both sides — the same
 * move the README prescribes for `form_widget` ("stub EC-CUBE's call to
 * render through the same form object").
 *
 * EC-CUBE's `url()` is its Symfony router: `url('homepage')` is `/`,
 * `url('product_detail', {id: 5})` is `/products/detail/5`. BeMart's
 * {@see \MyVendor\BeMart\Module\BeMartTwigExtension::url()} now resolves
 * names through Aura.Router and produces
 * those same real paths. So the EC-CUBE-side stub must resolve through the
 * SAME helper — otherwise the test would grade BeMart's correct port
 * against a deliberately wrong simplification.
 *
 * An unmapped route name falls back to the legacy `/{name}` form, exactly
 * as `BeMartTwigExtension` does, so a template referencing an EC-CUBE
 * route not yet in the map still diffs cleanly.
 */
final class EcCubeRouteStub
{
    /** Register `url` and `path` on $twig, both resolving like BeMart's Twig extension. */
    public static function register(Environment $twig): void
    {
        $routes = new BeMartTwigExtension();

        /** @param array<string, mixed> $params */
        $resolve = static fn (string $route, array $params = []): string => $routes->path($route, $params);

        $twig->addFunction(new TwigFunction('url', $resolve));
        $twig->addFunction(new TwigFunction('path', $resolve));
    }
}
