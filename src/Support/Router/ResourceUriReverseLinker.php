<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Router;

use Aura\Router\Exception\RouteNotFound as AuraRouteNotFound;
use Aura\Router\Route as AuraRoute;
use Aura\Router\RouterContainer;
use BEAR\Resource\ReverseLinkerInterface;
use Override;
use RuntimeException;

use function array_key_exists;
use function is_array;
use function is_string;
use function parse_str;
use function parse_url;
use function preg_match_all;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strtolower;
use function trim;

/**
 * Generates EC-CUBE-compatible HTML paths from BEAR page resource URIs.
 */
final class ResourceUriReverseLinker implements ReverseLinkerInterface
{
    public function __construct(private readonly RouterContainer $routes)
    {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function __invoke(string $uri, array $query): string
    {
        [$resourceUri, $data] = $this->resourceUriAndData($uri, $query);
        $bestPath = null;
        $bestScore = -1;

        foreach ($this->routes->getMap()->getRoutes() as $route) {
            if (! $route instanceof AuraRoute) {
                continue;
            }

            $candidate = $this->generateRoute($route, $resourceUri, $data);
            if ($candidate === null) {
                continue;
            }

            [$path, $score] = $candidate;
            if ($score > $bestScore) {
                $bestPath = $path;
                $bestScore = $score;
            }
        }

        return $bestPath ?? $uri;
    }

    /**
     * @param array<string, mixed> $query
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function resourceUriAndData(string $uri, array $query): array
    {
        $parts = parse_url($uri);
        if (! is_array($parts)) {
            return [$uri, $query];
        }

        $scheme = $parts['scheme'] ?? null;
        $host = $parts['host'] ?? null;
        if (! is_string($scheme) || ! is_string($host)) {
            return [$uri, $query];
        }

        $path = $parts['path'] ?? '';
        $uriQuery = [];
        $queryString = $parts['query'] ?? null;
        if (is_string($queryString) && $queryString !== '') {
            parse_str($queryString, $uriQuery);
        }

        /** @var array<string, mixed> $uriQuery */
        return [sprintf('%s://%s%s', $scheme, $host, $path), $uriQuery + $query];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{0: string, 1: int}|null
     */
    private function generateRoute(AuraRoute $route, string $resourceUri, array $data): array|null
    {
        $methods = $route->extras['bemart']['methods'] ?? null;
        if (! is_array($methods)) {
            return null;
        }

        $best = null;
        $bestScore = -1;
        foreach ($methods as $metadata) {
            if (! is_array($metadata) || ($metadata['resource'] ?? null) !== $resourceUri) {
                continue;
            }

            $path = $this->generatePath($route, $metadata, $data);
            if ($path === null) {
                continue;
            }

            $score = $this->score($route, $metadata, $path, $resourceUri, $data);
            if ($score > $bestScore) {
                $best = $path;
                $bestScore = $score;
            }
        }

        return $best === null ? null : [$best, $bestScore];
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $data
     */
    private function generatePath(AuraRoute $route, array $metadata, array $data): string|null
    {
        try {
            $path = $this->routes->getGenerator()->generate((string) $route->name, $this->routeData($metadata, $data));
        } catch (AuraRouteNotFound | RuntimeException) {
            return null;
        }

        return ! str_contains($path, '{') ? $path : null;
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $data
     */
    private function score(AuraRoute $route, array $metadata, string $path, string $resourceUri, array $data): int
    {
        $score = $this->pathParamCoverage($route, $metadata, $data) * 100;
        if ($path === $this->resourcePath($resourceUri)) {
            $score += 50;
        }

        if ((string) $route->name === $this->routeNameHint($resourceUri)) {
            $score += 25;
        }

        if (str_starts_with($path, '/block/')) {
            $score -= 10;
        }

        return $score;
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $data
     */
    private function pathParamCoverage(AuraRoute $route, array $metadata, array $data): int
    {
        preg_match_all('/{\s*([A-Za-z_][A-Za-z0-9_-]*)/', (string) $route->path, $matches);
        $paramMap = $metadata['paramMap'] ?? [];
        $coverage = 0;
        foreach ($matches[1] ?? [] as $routeParam) {
            $resourceParam = is_array($paramMap) && is_string($paramMap[$routeParam] ?? null)
                ? $paramMap[$routeParam]
                : $routeParam;
            if (array_key_exists($resourceParam, $data)) {
                $coverage++;
            }
        }

        return $coverage;
    }

    private function resourcePath(string $resourceUri): string
    {
        $path = parse_url($resourceUri, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/';
    }

    private function routeNameHint(string $resourceUri): string
    {
        $path = trim($this->resourcePath($resourceUri), '/');
        if ($path === '') {
            return 'homepage';
        }

        return strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '_', $path));
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function routeData(array $metadata, array $data): array
    {
        $routeData = $data;
        $paramMap = $metadata['paramMap'] ?? [];
        if (! is_array($paramMap)) {
            return $routeData;
        }

        foreach ($paramMap as $routeParam => $resourceParam) {
            if (! is_string($routeParam) || ! is_string($resourceParam)) {
                continue;
            }

            if (array_key_exists($routeParam, $routeData) || ! array_key_exists($resourceParam, $data)) {
                continue;
            }

            $routeData[$routeParam] = $data[$resourceParam];
        }

        return $routeData;
    }
}
