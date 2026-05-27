<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Router;

use MyVendor\BeMart\Router\RouteTable;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_keys;
use function file_get_contents;
use function preg_match_all;
use function sort;
use function str_contains;

final class TemplateRouteCoverageTest extends TestCase
{
    public function testAdminTemplateRouteReferencesExistInRouteTable(): void
    {
        $this->assertTemplateRouteCoverage('var/templates/Page/Admin');
    }

    public function testAllTemplateRouteReferencesExistInRouteTable(): void
    {
        $this->assertTemplateRouteCoverage('var/templates');
    }

    public function testRouteTableDoesNotExposeUnsupportedRoutes(): void
    {
        $unsupported = [];
        foreach (RouteTable::default()->routes as $route) {
            foreach (RouteTable::methodMetadataFor($route) as $metadata) {
                if (str_contains($metadata['resource'], 'unsupported-route')) {
                    $unsupported[] = $route->name . ' => ' . $metadata['resource'];
                }
            }
        }

        sort($unsupported);
        self::assertSame([], $unsupported);
    }

    private function assertTemplateRouteCoverage(string $directory): void
    {
        $table = RouteTable::default();
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
            if ($table->byName($routeName) === null) {
                $missing[] = $routeName;
            }
        }

        sort($missing);
        self::assertSame([], $missing);
    }
}
