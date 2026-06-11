<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function dirname;
use function file_get_contents;
use function str_contains;

/** Guards the Resource/Transfer boundary against context-name branching. */
final class AppContextBoundaryTest extends TestCase
{
    public function testResourceAndProvideCodeDoesNotReadApplicationContext(): void
    {
        $violations = [];
        foreach (['src/Resource', 'src/Provide'] as $dir) {
            foreach ($this->phpFiles(dirname(__DIR__, 2) . '/' . $dir) as $file) {
                $code = (string) file_get_contents($file->getPathname());
                if (str_contains($code, 'APP_CONTEXT') || str_contains($code, 'getenv(')) {
                    $violations[] = $file->getPathname();
                }
            }
        }

        $this->assertSame([], $violations);
    }

    /** @return iterable<SplFileInfo> */
    private function phpFiles(string $dir): iterable
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($files as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                yield $file;
            }
        }
    }
}
