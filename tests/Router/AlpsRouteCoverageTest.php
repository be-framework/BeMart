<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Router;

use Aura\Router\Map;
use Aura\Router\RouterContainer;
use PHPUnit\Framework\TestCase;

use function array_key_exists;
use function array_keys;
use function file_get_contents;
use function is_array;
use function json_decode;
use function sort;
use function sprintf;

final class AlpsRouteCoverageTest extends TestCase
{
    public function testEveryDefaultRouteMethodHasExistingAlpsDescriptor(): void
    {
        $descriptorIds = $this->alpsDescriptorIds();
        $implicit = [];
        $missing = [];

        foreach ($this->routerContainer()->getMap()->getRoutes() as $route) {
            /** @var mixed $methods */
            $methods = $route->extras['bemart']['methods'] ?? [];
            self::assertIsArray($methods);
            foreach (array_keys($methods) as $method) {
                /** @var mixed $metadata */
                $metadata = $methods[$method] ?? null;
                self::assertIsArray($metadata, sprintf('%s %s has no BeMart metadata.', $method, (string) $route->name));
                $alpsId = (string) ($metadata['alpsId'] ?? '');
                if (($metadata['alpsExplicit'] ?? false) !== true) {
                    $implicit[] = sprintf('%s %s', $method, (string) $route->name);
                }

                if (! array_key_exists($alpsId, $descriptorIds)) {
                    $missing[] = sprintf('%s %s => %s', $method, (string) $route->name, $alpsId);
                }
            }
        }

        sort($implicit);
        sort($missing);
        self::assertSame([], $implicit, 'Aura route extras must explicitly map routes to ALPS descriptors.');
        self::assertSame([], $missing, 'Aura route extras must reference descriptors present in alps.json.');
    }

    /** @return array<string, true> */
    private function alpsDescriptorIds(): array
    {
        $json = file_get_contents(__DIR__ . '/../../alps.json');
        self::assertIsString($json);
        $profile = json_decode($json, true);
        self::assertIsArray($profile);

        $ids = [];
        $this->collectDescriptorIds($profile['alps']['descriptor'] ?? [], $ids);

        return $ids;
    }

    /**
     * @param mixed $descriptors
     * @param array<string, true> $ids
     */
    private function collectDescriptorIds(mixed $descriptors, array &$ids): void
    {
        if (! is_array($descriptors)) {
            return;
        }

        foreach ($descriptors as $descriptor) {
            if (! is_array($descriptor)) {
                continue;
            }

            if (isset($descriptor['id'])) {
                $ids[(string) $descriptor['id']] = true;
            }

            $this->collectDescriptorIds($descriptor['descriptor'] ?? [], $ids);
        }
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
