<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use MyVendor\BeMart\Module\BeMartTwigExtension;
use Twig\Environment;
use Twig\TwigFunction;

/**
 * Registers `asset()` on a render-diff test's EC-CUBE-side Twig Environment.
 *
 * ## Why this is shared infrastructure, not an inline stub
 *
 * A render-diff test renders EC-CUBE's real template and BeMart's port and
 * asserts they agree. For the diff to be honest, a helper EC-CUBE's
 * template calls must be exercised IDENTICALLY on both sides — the same
 * move {@see EcCubeRouteStub} makes for `url()` / `path()`.
 *
 * EC-CUBE's `asset()` takes an optional second argument: the asset PACKAGE
 * (`assets.packages` in framework.yaml). The `default` storefront theme,
 * the `admin` theme and the webpack `bundle` output are physically
 * distinct asset roots served under different base paths
 * (`/`, `/template/admin/`, `/bundle/`). EC-CUBE's real `default_frame.twig`
 * calls `asset('front.bundle.js', 'bundle')`; its admin frame calls
 * `asset(..., 'admin')`. A stub that drops the package argument resolves
 * `front.bundle.js` to `/front.bundle.js`, while BeMart's
 * {@see BeMartTwigExtension::asset()} correctly yields
 * `/bundle/front.bundle.js` — a spurious diff that would grade BeMart's
 * correct port against a deliberately-wrong simplification.
 *
 * So the EC-CUBE-side stub resolves through the SAME package map BeMart
 * uses: {@see BeMartTwigExtension::asset()}, the faithful port of EC-CUBE's
 * `assets.packages` base-path map.
 */
final class EcCubeAssetStub
{
    /** Register `asset` on $twig, resolving through the EC-CUBE asset package map. */
    public static function register(Environment $twig): void
    {
        $extension = new BeMartTwigExtension();

        $twig->addFunction(new TwigFunction(
            'asset',
            static fn (string $path, string $package = ''): string => $extension->asset($path, $package),
        ));
    }
}
