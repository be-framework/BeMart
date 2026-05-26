<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Router;

use InvalidArgumentException;

use function array_key_exists;
use function explode;
use function http_build_query;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function preg_match;
use function preg_quote;
use function rawurldecode;
use function rawurlencode;
use function str_contains;
use function str_replace;
use function strtolower;
use function strtoupper;

/**
 * One entry in {@see RouteTable}: an EC-CUBE route NAME paired with the
 * EC-CUBE URL path pattern and the BEAR resource URI it dispatches to.
 *
 * Why both a path AND a name: the ported EC-CUBE templates link via
 * `url('route_name', params)` (see {@see \MyVendor\BeMart\Module\BeMartTwigExtension}),
 * while an HTTP request arrives as a concrete PATH. A single Route object
 * answers both — {@see match()} resolves a request path, {@see generate()}
 * builds the href a template needs — so the URLs templates emit and the
 * URLs the router resolves cannot drift apart.
 *
 * The path pattern uses EC-CUBE's `{placeholder}` syntax. Each placeholder
 * is mapped, via $paramMap, to the BEAR resource's PARAMETER name (EC-CUBE
 * names a product-detail path param `id`; the BeMart resource's `onGet`
 * takes `$productCode`). The router renames while extracting so the value
 * reaches the resource under the name the resource declares.
 */
final class Route
{
    /** @var non-empty-string|array<string, non-empty-string> */
    public readonly string|array $alpsId;

    /**
     * @param string               $name      EC-CUBE route name (e.g. `product_detail`).
     * @param list<string>          $methods   Upper-case HTTP verbs this route serves.
     * @param string               $path      EC-CUBE URL pattern with `{placeholder}` segments.
     * @param string               $resource  BEAR resource URI (e.g. `page://self/product`).
     * @param array<string,string> $paramMap       EC-CUBE placeholder name => BEAR resource param name.
     * @param string|null          $dispatchMethod Internal BEAR resource method, defaults to the HTTP method.
     * @param array<string,string> $defaults       Default params merged into a successful match.
     * @param array<string,string> $queryParamMap  EC-CUBE query/form param name => BEAR resource param name.
     * @param non-empty-string|array<string, non-empty-string>|null $alpsId ALPS descriptor id or method => descriptor id map.
     */
    public function __construct(
        public readonly string $name,
        public readonly array $methods,
        public readonly string $path,
        public readonly string $resource,
        public readonly array $paramMap = [],
        public readonly string|null $dispatchMethod = null,
        public readonly array $defaults = [],
        public readonly array $queryParamMap = [],
        string|array|null $alpsId = null,
    ) {
        $this->alpsId = $alpsId ?? AlpsRouteMap::for($name, $methods, $dispatchMethod);
    }

    /** Does this route serve $method (case-insensitive)? */
    public function allowsMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->methods, true);
    }

    /** The BEAR resource method to invoke for this HTTP method. */
    public function dispatchMethodFor(string $method): string
    {
        return strtolower($this->dispatchMethod ?? $method);
    }

    /**
     * The ALPS transition descriptor represented by this public route method.
     *
     * A route name can serve both a displayed state and a submitted action
     * (for example GET contact form vs POST submit).  ALPS captures those as
     * separate descriptors, so the router resolves the method-specific id.
     *
     * @return non-empty-string
     */
    public function alpsIdFor(string $method): string
    {
        if (! is_array($this->alpsId)) {
            return $this->alpsId;
        }

        $method = strtoupper($method);
        if (isset($this->alpsId[$method])) {
            return $this->alpsId[$method];
        }

        throw new InvalidArgumentException('Route "' . $this->name . '" has no ALPS descriptor for ' . $method . '.');
    }

    /**
     * Attempt to match a concrete request path against this route's pattern.
     *
     * Returns the extracted path parameters keyed by the BEAR resource
     * parameter name (renamed via $paramMap), or null when the path does
     * not fit the pattern. A pattern with no `{placeholder}` matches only
     * its exact literal path.
     *
     * Extracted values are URL-decoded. They are untrusted input; callers
     * pass them straight to the BEAR resource, whose Be Semantic
     * constructors format-validate before any sink touches them.
     *
     * @return array<string, string>|null
     */
    public function match(string $path): array|null
    {
        $regex = $this->toRegex();
        $matches = [];
        if (preg_match($regex, $path, $matches) !== 1) {
            return null;
        }

        $params = $this->defaults;
        /** @var array<array-key, string> $matches */
        foreach ($matches as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $resourceParam = $this->paramMap[$key] ?? $key;
            $params[$resourceParam] = rawurldecode($value);
        }

        return $params;
    }

    /**
     * Build the URL for this route — the inverse of {@see match()}.
     *
     * $params is keyed by EC-CUBE placeholder name (the template author's
     * vocabulary, e.g. `{id: 5}`). Placeholder values fill the path;
     * leftover params become the query string. This is what the
     * `url()` / `path()` Twig helpers call.
     *
     * @param array<string, int|string> $params
     */
    public function generate(array $params = []): string
    {
        $path = $this->path;
        $query = [];
        foreach ($params as $key => $value) {
            $placeholder = '{' . $key . '}';
            if (str_contains($path, $placeholder)) {
                $path = str_replace($placeholder, rawurlencode((string) $value), $path);

                continue;
            }

            $query[$key] = $value;
        }

        foreach ($this->defaults as $key => $value) {
            $placeholder = '{' . $key . '}';
            if (str_contains($path, $placeholder)) {
                $path = str_replace($placeholder, rawurlencode($value), $path);
            }
        }

        if ($query !== []) {
            return $path . '?' . http_build_query($query);
        }

        return $path;
    }

    /**
     * Compile the `{placeholder}` pattern into an anchored named-group regex.
     *
     * @return non-empty-string The literal `#^...$#` delimiters guarantee it.
     */
    private function toRegex(): string
    {
        $segments = explode('/', $this->path);
        $compiled = [];
        foreach ($segments as $segment) {
            $compiled[] = $this->compileSegment($segment);
        }

        return '#^' . implode('/', $compiled) . '$#';
    }

    /** Compile one path segment — a `{name}` placeholder or a literal. */
    private function compileSegment(string $segment): string
    {
        if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $segment, $m) !== 1) {
            return preg_quote($segment, '#');
        }

        // A path-param segment matches one non-slash run, captured under
        // the EC-CUBE placeholder name; match() renames it via $paramMap.
        return '(?<' . $m[1] . '>[^/]+)';
    }

    /** True when this route declares the given EC-CUBE placeholder. */
    public function hasPlaceholder(string $name): bool
    {
        return array_key_exists($name, $this->paramMap)
            || str_contains($this->path, '{' . $name . '}');
    }
}
