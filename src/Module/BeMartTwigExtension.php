<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Router\RouteTable;
use NumberFormatter;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

use function bin2hex;
use function http_build_query;
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
 *  - `url` / `path`    — Symfony routing helpers. These resolve an
 *                        EC-CUBE route NAME through the SAME
 *                        {@see RouteTable} the HTTP front controller's
 *                        {@see \MyVendor\BeMart\Router\Router} resolves
 *                        requests with. Sharing the one table is the point:
 *                        every href a ported template emits is, by
 *                        construction, a URL the router can dispatch back —
 *                        `url('product_detail', {id: 5})` yields
 *                        `/products/detail/5`, and a GET of that path
 *                        resolves to `page://self/product`. A route name
 *                        the table does not carry falls back to the legacy
 *                        `/{name}` form so an as-yet-unmapped EC-CUBE-ism
 *                        still renders a deterministic, diffable href.
 *
 * Every value produced here is deterministic, so the rendered HTML is
 * diffable against EC-CUBE's output (residual-diff verification).
 */
final class BeMartTwigExtension extends AbstractExtension
{
    private readonly RouteTable $routes;
    private readonly CsrfToken|null $csrf;

    /**
     * @param RouteTable|null $routes The shared route map. Defaults to
     *     {@see RouteTable::default()} so the extension can be constructed
     *     with no arguments (render tests, the Twig provider).
     */
    public function __construct(RouteTable|null $routes = null, CsrfToken|null $csrf = null)
    {
        $this->routes = $routes ?? RouteTable::default();
        $this->csrf = $csrf;
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
        if ($this->csrf instanceof CsrfToken) {
            return $this->csrf->token;
        }

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

    /** @param array<string, int|string> $params */
    public function url(string $route, array $params = []): string
    {
        return $this->path($route, $params);
    }

    /**
     * Resolve an EC-CUBE route name to a URL via the shared {@see RouteTable}.
     *
     * A mapped route generates its real EC-CUBE path with placeholders
     * filled (`product_detail` + `{id: 5}` -> `/products/detail/5`); any
     * leftover params become the query string. An unmapped name falls back
     * to the legacy `/{name}` form — the same deterministic shape the
     * pre-router helper produced — so a template referencing an EC-CUBE
     * route not yet in the table still renders a stable, diffable href.
     *
     * @param array<string, int|string> $params
     */
    public function path(string $route, array $params = []): string
    {
        $matched = $this->routes->byName($route);
        if ($matched !== null) {
            return $matched->generate($params);
        }

        $url = '/' . $route;
        if ($params !== []) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }
}
