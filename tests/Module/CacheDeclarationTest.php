<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use MyVendor\BeMart\Injector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionObject;
use SplFileInfo;

use function assert;
use function dirname;
use function get_class;
use function is_array;
use function ksort;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Ray.Aop keeps a cache declaration by extending the class
 *
 * A `final` resource, or one whose `onGet` is `final` or `private`, is instantiated plain: the
 * lookup never runs and the log stays empty rather than reporting a miss. Nothing else notices,
 * because a resource that never caches still answers correctly.
 */
final class CacheDeclarationTest extends TestCase
{
    private const ANNOTATION_NS = 'BEAR\RepositoryModule\Annotation\\';
    private const INTERCEPTOR_NS = 'BEAR\QueryRepository\\';
    private const RESOURCE_NS = 'MyVendor\BeMart\Resource\\';

    /** @return array<string, array{class-string, string}> */
    public static function declarations(): array
    {
        $cases = [];
        foreach (self::resourceClasses() as $class) {
            $reflection = new ReflectionClass($class);
            foreach ($reflection->getAttributes() as $attribute) {
                if (! str_starts_with($attribute->getName(), self::ANNOTATION_NS)) {
                    continue;
                }

                $name = substr($class, strlen(self::RESOURCE_NS));
                $cases[$name . ' onGet'] = [$class, 'onGet'];
            }
        }

        ksort($cases);

        return $cases;
    }

    /** @param class-string $class */
    #[DataProvider('declarations')]
    public function testDeclarationReachesACacheInterceptor(string $class, string $method): void
    {
        $instance = Injector::getInstance('cli-fake-hal-app')->getInstance($class);
        self::assertNotSame($class, get_class($instance), 'the class is instantiated plain, so no cache declaration on it can take effect');

        $bindings = (new ReflectionObject($instance))->getProperty('bindings');
        $map = $bindings->getValue($instance);
        assert(is_array($map));
        self::assertArrayHasKey($method, $map, "{$method} is not intercepted");

        foreach ($map[$method] as $interceptor) {
            if (str_starts_with(get_class($interceptor), self::INTERCEPTOR_NS)) {
                return;
            }
        }

        self::fail("{$method} is intercepted, but not by the cache");
    }

    /** @return list<class-string> */
    private static function resourceClasses(): array
    {
        $dir = dirname(__DIR__, 2) . '/src/Resource';
        $classes = [];
        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            /** @var class-string $class */
            $class = self::RESOURCE_NS . str_replace('/', '\\', substr($file->getPathname(), strlen($dir) + 1, -4));
            $classes[] = $class;
        }

        return $classes;
    }
}
