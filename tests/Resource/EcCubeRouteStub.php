<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use MyVendor\BeMart\Router\RouteTable;
use Twig\Environment;
use Twig\TwigFunction;

use function http_build_query;

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
 * names through {@see RouteTable} and produces those same real paths. So
 * the EC-CUBE-side stub must resolve through the SAME table — otherwise
 * the test would grade BeMart's correct port against a deliberately wrong
 * simplification.
 *
 * An unmapped route name falls back to the legacy `/{name}` form, exactly
 * as `BeMartTwigExtension` does, so a template referencing an EC-CUBE
 * route not yet in the table still diffs cleanly.
 */
final class EcCubeRouteStub
{
    /** Register `url` and `path` on $twig, both resolving through {@see RouteTable}. */
    public static function register(Environment $twig): void
    {
        $table = RouteTable::default();

        /** @param array<string, int|string> $params */
        $resolve = static function (string $route, array $params = []) use ($table): string {
            $matched = $table->byName($route);
            if ($matched !== null) {
                return $matched->generate($params);
            }

            return '/' . $route . ($params !== [] ? '?' . http_build_query($params) : '');
        };

        $twig->addFunction(new TwigFunction('url', $resolve));
        $twig->addFunction(new TwigFunction('path', $resolve));
    }
}
