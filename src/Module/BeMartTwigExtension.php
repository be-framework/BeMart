<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
use MyVendor\BeMart\Router\RouteTable;
use MyVendor\BeMart\Router\RouteUrlGenerator;
use NumberFormatter;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

use function bin2hex;
use function is_string;
use function random_bytes;

/**
 * Twig helpers the EC-CUBE template port relies on.
 *
 * The BeMart storefront templates are PORTS of EC-CUBE 4.3's default-theme
 * Twig files (see var/templates/README.md). EC-CUBE's templates call a
 * handful of Twig functions/filters provided by Symfony / EC-CUBE's own
 * `EccubeExtension`. To keep the ported markup byte-faithful while keeping
 * the rebinding minimal, this extension supplies the small subset the Cart
 * port uses:
 *
 *  - `price`  filter   — EC-CUBE's `EccubeExtension::getPriceFilter`. Same
 *                        impl: NumberFormatter CURRENCY for JPY, yielding
 *                        e.g. `￥1,200`.
 *  - `asset`  function — EC-CUBE/Symfony asset(). BeMart has no asset-hash
 *                        pipeline, so this resolves a path to a static URL.
 *                        The optional second argument is EC-CUBE's asset
 *                        PACKAGE (`assets.packages` in framework.yaml): the
 *                        `default` theme, the `admin` theme and the webpack
 *                        `bundle` output are physically distinct asset roots
 *                        — EC-CUBE serves them under different base paths so
 *                        same-named files (`assets/js/function.js` exists in
 *                        BOTH themes, byte-different) never collide. This
 *                        method is a faithful port of that package map: the
 *                        deployed `public/` tree mirrors EC-CUBE's served
 *                        URLs (`/assets`, `/template/admin/assets`,
 *                        `/bundle`), so every package resolves to a real,
 *                        byte-identical EC-CUBE file.
 *  - `url` / `path`    — Symfony routing helpers kept for ported EC-CUBE
 *                        templates. They delegate path generation to
 *                        Aura.Router through {@see RouteUrlGenerator}, so
 *                        template links and HTTP dispatch share the same
 *                        route definitions.
 *
 * Every value produced here is deterministic, so the rendered HTML is
 * diffable against EC-CUBE's output (residual-diff verification).
 */
final class BeMartTwigExtension extends AbstractExtension
{
    private readonly RouteUrlGenerator $urls;

    /**
     * @param RouteTable|RouteUrlGenerator|null $routes The shared route metadata
     *     or URL generator. Defaults to {@see RouteUrlGenerator} backed by the
     *     default route table so the extension can be constructed with no
     *     arguments.
     */
    public function __construct(RouteTable|RouteUrlGenerator|null $routes = null)
    {
        $this->urls = $routes instanceof RouteUrlGenerator
            ? $routes
            : new RouteUrlGenerator($routes);
    }

    /** @return list<TwigFilter> */
    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('price', [$this, 'price']),
        ];
    }

    /** @return list<TwigFunction> */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset', [$this, 'asset']),
            new TwigFunction('url', [$this, 'url']),
            new TwigFunction('path', [$this, 'path']),
            new TwigFunction('csrf_token', [$this, 'csrfToken']),
            new TwigFunction('csrf_token_for_anchor', [$this, 'csrfTokenForAnchor']),
        ];
    }

    /**
     * Mirror of EC-CUBE EccubeExtension::getPriceFilter for the JPY store.
     */
    public function price(int|float|null $number): string
    {
        $formatter = new NumberFormatter('ja_JP', NumberFormatter::CURRENCY);

        return (string) $formatter->formatCurrency((float) ($number ?? 0), 'JPY');
    }

    /**
     * Port of EC-CUBE's asset() — resolves $path under its asset PACKAGE.
     *
     * EC-CUBE's `assets.packages` (framework.yaml) gives each package a
     * `base_path`; the deployed `public/` tree mirrors those served URLs:
     *
     *  - default (no package) — the `default` storefront theme  -> `/`
     *  - `admin`              — the admin theme                 -> `/template/admin/`
     *  - `bundle`             — the webpack output               -> `/bundle/`
     *  - `save_image`         — uploaded product imagery         -> `/`
     *
     * `save_image` paths in the BeMart port are written as ordinary
     * `assets/img/...` literals (the no-image placeholder is deployed under
     * `public/assets/img/common/`), so it resolves like the default package.
     */
    public function asset(string $path, string $package = ''): string
    {
        $prefix = match ($package) {
            'admin' => '/template/admin/',
            'bundle' => '/bundle/',
            default => '/',
        };

        return $prefix . $path;
    }

    /**
     * Minimal EC-CUBE-compatible CSRF widget for ported Twig templates.
     *
     * The html front controller starts PHP's session before rendering, so the
     * generated token is stored under the same flat key that the production
     * CSRF adapter validates on POST. In CLI/render-test contexts with no
     * active session, returning a fresh non-empty token is enough to keep
     * templates renderable.
     */
    public function csrfToken(string $tokenId = ''): string
    {
        /** @var mixed $stored */
        $stored = $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] ?? null;
        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        $token = bin2hex(random_bytes(32));
        if (isset($_SESSION)) {
            $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = $token;
        }

        return $token;
    }

    /** EC-CUBE's anchor-token helper; BeMart reuses the same request token. */
    public function csrfTokenForAnchor(string $tokenId = ''): string
    {
        return $this->csrfToken($tokenId);
    }

    /** @param array<string, mixed> $params */
    public function url(string $route, array $params = []): string
    {
        return $this->path($route, $params);
    }

    /**
     * Resolve an EC-CUBE route name to a URL via Aura.Router.
     *
     * A mapped route generates its real EC-CUBE path with placeholders
     * filled (`product_detail` + `{id: 5}` -> `/products/detail/5`); any
     * leftover params become the query string. An unmapped name falls back
     * to the legacy `/{name}` form so a template referencing an EC-CUBE
     * route not yet in the map still renders a stable, diffable href.
     *
     * @param array<string, mixed> $params
     */
    public function path(string $route, array $params = []): string
    {
        return $this->urls->generate($route, $params);
    }
}
