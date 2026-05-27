<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Router;

use Aura\Router\Map;
use Aura\Router\Route as AuraRoute;
use Aura\Router\RouterContainer;
use LogicException;

use function array_key_exists;
use function array_keys;
use function sprintf;
use function strtolower;
use function strtoupper;

/**
 * Builds the Aura.Router map from BeMart route metadata.
 *
 * Aura owns path matching, placeholder extraction, method matching, and path
 * generation. BeMart keeps only the semantic dispatch metadata Aura does not
 * know about: EC-CUBE aliases, BEAR resource URI, internal resource method,
 * and wire-name-to-resource-name maps.
 *
 * @psalm-type MethodMetadata = array{
 *     resource: string,
 *     dispatchMethod: string,
 *     paramMap: array<string, string>,
 *     defaults: array<string, string>,
 *     queryParamMap: array<string, string>
 * }
 */
final class AuraRouteMap
{
    private const EXTRA_KEY = 'bemart';
    private const METHODS_KEY = 'methods';

    public function __construct(private readonly RouteTable $table)
    {
    }

    public function newContainer(): RouterContainer
    {
        $container = new RouterContainer();
        $container->setMapBuilder(fn (Map $map): null => $this->build($map));

        return $container;
    }

    public function build(Map $map): null
    {
        foreach ($this->groupByName() as $name => $routes) {
            $first = $routes[0];
            $methods = $this->methodMetadata($name, $routes);

            $route = $map->route($name, $first->path, $name);
            $route->allows(array_keys($methods));
            $route->extras([
                self::EXTRA_KEY => [
                    self::METHODS_KEY => $methods,
                ],
            ]);
        }

        return null;
    }

    /** @return MethodMetadata */
    public static function metadataFor(AuraRoute $route, string $method): array
    {
        /** @var mixed $bemart */
        $bemart = $route->extras[self::EXTRA_KEY] ?? null;
        if (! isset($bemart[self::METHODS_KEY]) || ! is_array($bemart[self::METHODS_KEY])) {
            throw new LogicException(sprintf('Aura route "%s" has no BeMart metadata.', (string) $route->name));
        }

        /** @var array<string, MethodMetadata> $methods */
        $methods = $bemart[self::METHODS_KEY];
        $method = strtoupper($method);
        if (! array_key_exists($method, $methods)) {
            throw new LogicException(sprintf('Aura route "%s" has no metadata for %s.', (string) $route->name, $method));
        }

        return $methods[$method];
    }

    /** @return array<string, list<Route>> */
    private function groupByName(): array
    {
        $groups = [];
        foreach ($this->table->routes as $route) {
            $groups[$route->name][] = $route;
        }

        return $groups;
    }

    /**
     * @param list<Route> $routes
     * @return array<string, MethodMetadata>
     */
    private function methodMetadata(string $name, array $routes): array
    {
        $path = $routes[0]->path;
        $methods = [];
        foreach ($routes as $route) {
            if ($route->path !== $path) {
                throw new LogicException(sprintf(
                    'Route "%s" cannot be represented as one Aura route because it has multiple paths: %s and %s.',
                    $name,
                    $path,
                    $route->path,
                ));
            }

            foreach ($route->methods as $method) {
                $method = strtoupper($method);
                if (array_key_exists($method, $methods)) {
                    throw new LogicException(sprintf('Route "%s" defines %s more than once.', $name, $method));
                }

                $methods[$method] = [
                    'resource' => $route->resource,
                    'dispatchMethod' => strtolower($route->dispatchMethod ?? $method),
                    'paramMap' => $route->paramMap,
                    'defaults' => $route->defaults,
                    'queryParamMap' => $route->queryParamMap,
                ];
            }
        }

        if ($methods === []) {
            throw new LogicException(sprintf('Route "%s" must expose at least one HTTP method.', $name));
        }

        return $methods;
    }
}
