<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Csrf\Attribute\CsrfToken;
use BEAR\Csrf\Attribute\SameOrigin;
use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;

use function class_exists;
use function dirname;
use function implode;
use function is_subclass_of;
use function sort;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;

use const DIRECTORY_SEPARATOR;

/**
 * Pages are the browser boundary, so every unsafe page verb sits behind both BEAR.Csrf gates;
 * app:// resources are reached in-process from a page that already passed them and carry none.
 */
final class CsrfProtectionCoverageTest extends TestCase
{
    private const UNSAFE_VERBS = ['onPost', 'onPut', 'onPatch', 'onDelete'];
    private const GATES = [CsrfToken::class, SameOrigin::class];

    public function testEveryUnsafePageVerbSitsBehindBothGates(): void
    {
        $missing = [];
        foreach ($this->unsafeMethods('MyVendor\\BeMart\\Resource\\Page\\') as $name => $method) {
            foreach (self::GATES as $gate) {
                if ($method->getAttributes($gate) === []) {
                    $missing[] = $name . ' lacks #[' . $gate . ']';
                }
            }
        }

        $this->assertSame([], $missing, "Unsafe Page verbs must declare both BEAR.Csrf gates:\n" . implode("\n", $missing));
    }

    public function testAppResourcesCarryNoGate(): void
    {
        $gated = [];
        foreach ($this->unsafeMethods('MyVendor\\BeMart\\Resource\\App\\') as $name => $method) {
            foreach (self::GATES as $gate) {
                if ($method->getAttributes($gate) !== []) {
                    $gated[] = $name . ' declares #[' . $gate . ']';
                }
            }
        }

        $this->assertSame([], $gated, "app:// resources have no browser to gate; the page in front of them does:\n" . implode("\n", $gated));
    }

    public function testUnsafeVerbsDoNotAcceptACsrfTokenParameter(): void
    {
        $violations = [];
        foreach ($this->unsafeMethods('MyVendor\\BeMart\\Resource\\') as $name => $method) {
            foreach ($method->getParameters() as $parameter) {
                if ($parameter->getName() === 'csrfToken') {
                    $violations[] = $name . '($csrfToken)';
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "The token is a request field the interceptor reads, never a Resource argument:\n" . implode("\n", $violations),
        );
    }

    /** @return array<string, ReflectionMethod> "DeclaringClass::verb" => method, for concrete resources under $namespacePrefix */
    private function unsafeMethods(string $namespacePrefix): array
    {
        $methods = [];
        foreach ($this->resourceClasses() as $class) {
            if (! is_subclass_of($class, ResourceObject::class) || ! str_starts_with($class, $namespacePrefix)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract()) {
                continue;
            }

            foreach (self::UNSAFE_VERBS as $verb) {
                if (! $reflection->hasMethod($verb)) {
                    continue;
                }

                $method = $reflection->getMethod($verb);
                if (! $method->isPublic()) {
                    continue;
                }

                $methods[$method->getDeclaringClass()->getName() . '::' . $verb] = $method;
            }
        }

        return $methods;
    }

    /** @return list<class-string> */
    private function resourceClasses(): array
    {
        $resourceDir = dirname(__DIR__, 2) . '/src/Resource';
        $classes = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resourceDir));

        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path = $file->getPathname();
            if (! str_ends_with($path, '.php')) {
                continue;
            }

            $relative = substr($path, strlen($resourceDir) + 1, -4);
            $class = 'MyVendor\\BeMart\\Resource\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
            if (! class_exists($class)) {
                $this->fail('Resource class could not be autoloaded: ' . $class);
            }

            $classes[] = $class;
        }

        sort($classes);

        return $classes;
    }
}
