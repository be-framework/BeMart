<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use NumberFormatter;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

use function http_build_query;
use function is_int;

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
 *                        pipeline, so this is a `/`-prefixed passthrough.
 *  - `url` / `path`    — Symfony routing helpers. BeMart has no Symfony
 *                        router; these build a stable `/{route}` URL (with
 *                        query string for params) so the ported <a>/<form>
 *                        markup keeps real, deterministic href values.
 *
 * Every value produced here is deterministic, so the rendered HTML is
 * diffable against EC-CUBE's output (residual-diff verification).
 */
final class BeMartTwigExtension extends AbstractExtension
{
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

    public function asset(string $path): string
    {
        return '/' . $path;
    }

    /** @param array<string, int|string> $params */
    public function url(string $route, array $params = []): string
    {
        return $this->path($route, $params);
    }

    /** @param array<string, int|string> $params */
    public function path(string $route, array $params = []): string
    {
        $url = '/' . $route;
        if ($params !== []) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }
}
