<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Router;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function preg_match_all;
use function sprintf;

use const PREG_SET_ORDER;

final class TemplateCanonicalUrlTest extends TestCase
{
    public function testTemplatesDoNotUseRouteHelperFunctions(): void
    {
        $violations = [];
        foreach ($this->twigFiles() as $file) {
            $contents = (string) file_get_contents($file->getPathname());
            if (preg_match_all('/\b(?:url|path)\s*\(/', $contents, $matches, PREG_SET_ORDER) > 0) {
                $violations[] = $file->getPathname();
            }
        }

        $this->assertSame([], $violations);
    }

    public function testTemplatesDoNotReferenceLegacyRouteNames(): void
    {
        $legacyRouteNames = [
            'product_list',
            'product_detail',
            'mypage_history',
            'admin_product_csv',
            'admin_product_product_edit',
            'admin_order_edit',
            'admin_setting_shop_csv',
        ];
        $violations = [];
        foreach ($this->twigFiles() as $file) {
            $contents = (string) file_get_contents($file->getPathname());
            foreach ($legacyRouteNames as $routeName) {
                if (str_contains($contents, $routeName)) {
                    $violations[] = sprintf('%s: %s', $file->getPathname(), $routeName);
                }
            }
        }

        $this->assertSame([], $violations);
    }

    /** @return list<SplFileInfo> */
    private function twigFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/../../var/templates'),
        );
        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            if ($file->getExtension() === 'twig') {
                $files[] = $file;
            }
        }

        return $files;
    }
}
