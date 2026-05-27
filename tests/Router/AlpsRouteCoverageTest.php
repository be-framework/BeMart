<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Router;

use MyVendor\BeMart\Router\AlpsRouteMap;
use MyVendor\BeMart\Router\RouteTable;
use PHPUnit\Framework\TestCase;

use function array_key_exists;
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
        $missing = [];
        $implicit = [];

        foreach (RouteTable::default()->routes as $route) {
            if (! AlpsRouteMap::has($route->name)) {
                $implicit[] = $route->name;
            }

            foreach ($route->methods as $method) {
                $alpsId = AlpsRouteMap::forRouteMethod($route, $method);
                if (! array_key_exists($alpsId, $descriptorIds)) {
                    $missing[] = sprintf('%s %s => %s', $method, $route->name, $alpsId);
                }
            }
        }

        sort($implicit);
        sort($missing);
        self::assertSame([], $implicit, 'RouteTable routes must be explicitly mapped to ALPS descriptors.');
        self::assertSame([], $missing, 'RouteTable routes must reference descriptors present in alps.json.');
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
}
