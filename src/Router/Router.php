<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Router;

use Aura\Router\Matcher;
use Aura\Router\Route as AuraRoute;
use Aura\Router\Rule\Allows;
use Nyholm\Psr7\ServerRequest;

use function rtrim;
use function sprintf;
use function strtoupper;

/**
 * Resolves an incoming HTTP `(method, path)` to BeMart dispatch metadata.
 *
 * Aura.Router performs the routing mechanics: path matching, placeholder
 * extraction, and HTTP method matching. This adapter only translates the
 * matched Aura route extras into a BEAR resource URI/method pair.
 */
final class Router
{
    private readonly Matcher $matcher;

    public function __construct(RouteTable $table)
    {
        $this->matcher = (new AuraRouteMap($table))->newContainer()->getMatcher();
    }

    /**
     * Match a request path to a resource.
     *
     * Trailing slashes are normalised away (`/cart/` == `/cart`), except
     * the site root which stays `/`. Aura distinguishes 404 and 405 via the
     * failed rule, which the entry point maps to HTTP semantics.
     *
     * @param string $method HTTP verb (any case).
     * @param string $path   URL path component of REQUEST_URI (untrusted).
     *
     * @throws RouteNotFoundException         No route pattern matches $path.
     * @throws RouteMethodNotAllowedException A route matches $path but not $method.
     */
    public function match(string $method, string $path): MatchedRoute
    {
        $normalizedPath = $this->normalize($path);
        $method = strtoupper($method);
        $request = new ServerRequest($method, $normalizedPath);
        $route = $this->matcher->match($request);
        if (! $route instanceof AuraRoute) {
            $failed = $this->matcher->getFailedRoute();
            if ($failed instanceof AuraRoute && $failed->failedRule === Allows::class) {
                throw new RouteMethodNotAllowedException(
                    sprintf('No route serves %s on "%s".', $method, $normalizedPath),
                );
            }

            throw new RouteNotFoundException(sprintf('No route matches "%s".', $normalizedPath));
        }

        $metadata = AuraRouteMap::metadataFor($route, $method);

        return new MatchedRoute(
            (string) $route->name,
            $metadata['resource'],
            $metadata['dispatchMethod'],
            $this->resourceParams($route->attributes, $metadata['paramMap'], $metadata['defaults']),
            $metadata['queryParamMap'],
        );
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

    /**
     * @param array<string, mixed>  $attributes Aura path attributes keyed by EC-CUBE placeholder name.
     * @param array<string, string> $paramMap   EC-CUBE placeholder name => BEAR resource param name.
     * @param array<string, string> $defaults   BEAR resource params supplied by route metadata.
     * @return array<string, string>
     */
    private function resourceParams(array $attributes, array $paramMap, array $defaults): array
    {
        $params = $defaults;
        foreach ($attributes as $key => $value) {
            $resourceParam = $paramMap[$key] ?? $key;
            $params[$resourceParam] = (string) $value;
        }

        return $params;
    }
}
