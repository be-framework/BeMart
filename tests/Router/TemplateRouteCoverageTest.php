<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Router;

use Aura\Router\Exception\RouteNotFound as AuraRouteNotFound;
use Aura\Router\Map;
use Aura\Router\RouterContainer;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_keys;
use function file_get_contents;
use function is_array;
use function preg_match_all;
use function sort;
use function str_contains;

final class TemplateRouteCoverageTest extends TestCase
{
    public function testAdminTemplateRouteReferencesExistInAuraRouteMap(): void
    {
        $this->assertTemplateRouteCoverage('var/templates/Page/Admin');
    }

    public function testAllTemplateRouteReferencesExistInAuraRouteMap(): void
    {
        $this->assertTemplateRouteCoverage('var/templates');
    }

    public function testAuraRouteMapDoesNotExposeUnsupportedRoutes(): void
    {
        $unsupported = [];
        foreach ($this->routerContainer()->getMap()->getRoutes() as $route) {
            /** @var mixed $methods */
            $methods = $route->extras['bemart']['methods'] ?? [];
            self::assertIsArray($methods);
            foreach ($methods as $metadata) {
                if (is_array($metadata) && str_contains((string) ($metadata['resource'] ?? ''), 'unsupported-route')) {
                    $unsupported[] = (string) $route->name . ' => ' . (string) $metadata['resource'];
                }
            }
        }

        sort($unsupported);
        self::assertSame([], $unsupported);
    }

    private function assertTemplateRouteCoverage(string $directory): void
    {
        $map = $this->routerContainer()->getMap();
        $refs = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($files as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'twig') {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            self::assertIsString($source);
            preg_match_all("/\\b(?:url|path)\\('([^']+)'/", $source, $matches);
            foreach ($matches[1] as $routeName) {
                $refs[$routeName] = true;
            }
        }

        $missing = [];
        foreach (array_keys($refs) as $routeName) {
            try {
                $map->getRoute($routeName);
            } catch (AuraRouteNotFound) {
                $missing[] = $routeName;
            }
        }

        sort($missing);
        self::assertSame([], $missing);
    }

    private function routerContainer(): RouterContainer
    {
        $container = new RouterContainer();
        /** @var callable(Map): null $routes */
        $routes = require __DIR__ . '/../../config/aura-routes.php';
        $container->setMapBuilder($routes);

        return $container;
    }
}
