<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Router;

use function rtrim;
use function sprintf;
use function strtoupper;

/**
 * Resolves an incoming HTTP `(method, path)` to a BEAR resource URI.
 *
 * This is the "proper front controller" the original public/index.php
 * header comment deferred to a later slice. The minimal dispatch mapped
 * `REQUEST_URI` path verbatim onto `page://self{path}`, so a
 * template-emitted EC-CUBE URL (`/products/detail/5`, `/help_tradelaw`)
 * never reached the right resource — it fell through to BEAR and surfaced
 * as an uncaught `Unbound` (HTTP 200 + Xdebug stack trace).
 *
 * The Router walks {@see RouteTable} — the single source of truth shared
 * with the `url()` / `path()` Twig helpers — so every URL a ported
 * template links to is, by construction, a URL the router resolves.
 *
 * Failure modes are explicit, dedicated exceptions (no generic throwables)
 * so the entry point can translate them to BEAR `Code` semantics:
 *   - {@see RouteNotFoundException}        -> 404
 *   - {@see RouteMethodNotAllowedException} -> 405
 */
final class Router
{
    public function __construct(private readonly RouteTable $table)
    {
    }

    /**
     * Match a request path to a resource.
     *
     * Trailing slashes are normalised away (`/cart/` == `/cart`), except
     * the site root which stays `/`. When several routes share a path
     * (EC-CUBE's `contact` GET vs `contact_confirm` POST both sit at
     * `/contact`), the method narrows the choice; a path that matches but
     * with no method-compatible route raises 405, not 404.
     *
     * @param string $method  HTTP verb (any case).
     * @param string $path    URL path component of REQUEST_URI (untrusted).
     *
     * @throws RouteNotFoundException        No route pattern matches $path.
     * @throws RouteMethodNotAllowedException A route matches $path but not $method.
     */
    public function match(string $method, string $path): MatchedRoute
    {
        $normalizedPath = $this->normalize($path);
        $method = strtoupper($method);

        $pathMatched = false;
        foreach ($this->table->routes as $route) {
            $params = $route->match($normalizedPath);
            if ($params === null) {
                continue;
            }

            $pathMatched = true;
            if ($route->allowsMethod($method)) {
                return new MatchedRoute(
                    $route->name,
                    $route->resource,
                    $route->dispatchMethodFor($method),
                    $params,
                    $route->queryParamMap,
                );
            }
        }

        if ($pathMatched) {
            throw new RouteMethodNotAllowedException(
                sprintf('No route serves %s on "%s".', $method, $normalizedPath),
            );
        }

        throw new RouteNotFoundException(sprintf('No route matches "%s".', $normalizedPath));
    }

    /** Strip a trailing slash, but never reduce the site root to empty. */
    private function normalize(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        $trimmed = rtrim($path, '/');

        return $trimmed === '' ? '/' : $trimmed;
    }
}
