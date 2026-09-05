<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use MyVendor\BeMart\Be\Reason\Service\ChangesProductCorpus;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

use function array_map;
use function basename;
use function dirname;
use function file_get_contents;
use function glob;
use function interface_exists;
use function preg_match;
use function preg_quote;
use function sprintf;
use function str_contains;

/**
 * Every write that changes the product corpus announces it
 *
 * The corpus is cached until an invalidation reaches it, so a forgotten announcement is not a
 * slow page - it is a wrong page, for as long as the entry lives. The interfaces mark which
 * operations change it ({@see ChangesProductCorpus}); this walks the Finals that call them.
 *
 * The sweep is structural: it sees a Final that injects a marked interface and calls it, not one
 * that reaches the write through a service (the class CSV imports). Those, and the paths a sweep
 * cannot tell apart, are pinned by execution in AdminProductCacheAnnouncementTest,
 * AdminCategoryTest and ClassCsvTransitionsTest with a recording invalidator.
 */
final class CorpusAnnouncementCoverageTest extends TestCase
{
    /** @return list<array{string, string}> interface short name, method */
    private function markedOperations(): array
    {
        $marked = [];
        foreach ((array) glob(dirname(__DIR__, 2) . '/src/Reason/Query/*Interface.php') as $file) {
            $class = 'MyVendor\\BeMart\\Be\\Reason\\Query\\' . basename((string) $file, '.php');
            if (! interface_exists($class)) {
                continue;
            }

            foreach ((new ReflectionClass($class))->getMethods() as $method) {
                if ($method->getAttributes(ChangesProductCorpus::class) !== []) {
                    $marked[] = [$class, $method->getName()];
                }
            }
        }

        return $marked;
    }

    public function testTheMarkIsUsed(): void
    {
        // A green suite below would otherwise be indistinguishable from a mark nobody applied.
        $this->assertNotEmpty($this->markedOperations(), 'no operation declares that it changes the corpus');
    }

    public function testEveryFinalCallingAMarkedOperationAnnouncesTheChange(): void
    {
        $marked = $this->markedOperations();
        $missing = [];

        foreach ((array) glob(dirname(__DIR__, 2) . '/src/Final/*.php') as $file) {
            $source = (string) file_get_contents((string) $file);
            foreach ($marked as [$class, $method]) {
                $short = (new ReflectionClass($class))->getShortName();
                if (! str_contains($source, $short)) {
                    continue;
                }

                // The parameter this Final injects the interface as, then a call on it.
                if (preg_match('/' . preg_quote($short, '/') . '\s+\$(\w+)/', $source, $m) !== 1) {
                    continue;
                }

                if (preg_match('/\$' . $m[1] . '->' . preg_quote($method, '/') . '\(/', $source) !== 1) {
                    continue;
                }

                if (str_contains($source, 'invalidateCorpus()')) {
                    continue;
                }

                $missing[] = sprintf('%s calls %s::%s() without announcing it', basename((string) $file, '.php'), $short, $method);
            }
        }

        $this->assertSame([], array_map('strval', $missing));
    }
}
