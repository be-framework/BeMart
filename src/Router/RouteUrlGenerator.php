<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Router;

use Aura\Router\Exception\RouteNotFound as AuraRouteNotFound;
use Aura\Router\Generator as AuraGenerator;
use Aura\Router\Map;

use function array_key_exists;
use function http_build_query;
use function preg_match_all;

/**
 * EC-CUBE route-name URL generator backed by Aura.Router.
 *
 * Aura generates the path and encodes placeholder values. The only compatibility
 * work left here is Symfony-style query-string appending for parameters that do
 * not correspond to path placeholders, plus the legacy deterministic fallback
 * for unmapped ported-template route names.
 */
final class RouteUrlGenerator
{
    private readonly AuraGenerator $generator;
    private readonly Map $map;

    public function __construct(RouteTable|null $table = null)
    {
        $container = (new AuraRouteMap($table ?? RouteTable::default()))->newContainer();
        $this->map = $container->getMap();
        $this->generator = $container->getGenerator();
    }

    /** @param array<string, mixed> $params */
    public function generate(string $route, array $params = []): string
    {
        try {
            $path = $this->generator->generate($route, $params);
            $query = $this->queryParams($route, $params);
        } catch (AuraRouteNotFound) {
            $path = '/' . $route;
            $query = $params;
        }

        if ($query === []) {
            return $path;
        }

        return $path . '?' . http_build_query($query);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function queryParams(string $route, array $params): array
    {
        $pathParamNames = $this->pathParamNames($route);
        $query = [];
        foreach ($params as $key => $value) {
            if (! array_key_exists($key, $pathParamNames)) {
                $query[$key] = $value;
            }
        }

        return $query;
    }

    /** @return array<string, true> */
    private function pathParamNames(string $route): array
    {
        $auraRoute = $this->map->getRoute($route);
        preg_match_all(AuraGenerator::REGEX, (string) $auraRoute->path, $matches);

        $names = [];
        foreach ($matches[1] as $name) {
            $names[$name] = true;
        }

        return $names;
    }
}
