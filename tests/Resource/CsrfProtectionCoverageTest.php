<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\TestCase;
use Ray\Csrf\Attribute\CsrfToken;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

use function class_exists;
use function dirname;
use function implode;
use function is_subclass_of;
use function sort;
use function str_ends_with;
use function str_replace;
use function strlen;
use function substr;

use const DIRECTORY_SEPARATOR;

final class CsrfProtectionCoverageTest extends TestCase
{
    public function testAllMutatingResourceMethodsAreCsrfProtected(): void
    {
        $missing = [];

        foreach ($this->resourceClasses() as $class) {
            if (! is_subclass_of($class, ResourceObject::class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            foreach (['onPost', 'onPut', 'onPatch', 'onDelete'] as $methodName) {
                if (! $reflection->hasMethod($methodName)) {
                    continue;
                }

                $method = $reflection->getMethod($methodName);
                if (! $method->isPublic() || $method->getDeclaringClass()->getName() !== $class) {
                    continue;
                }

                if ($method->getAttributes(CsrfToken::class) === []) {
                    $missing[] = $class . '::' . $methodName;
                }
            }
        }

        $this->assertSame(
            [],
            $missing,
            "Mutating Resource methods must declare #[CsrfToken]:\n" . implode("\n", $missing),
        );
    }

    public function testMutatingResourceMethodsDoNotAcceptCsrfTokenParameter(): void
    {
        $violations = [];

        foreach ($this->resourceClasses() as $class) {
            if (! is_subclass_of($class, ResourceObject::class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            foreach (['onPost', 'onPut', 'onPatch', 'onDelete'] as $methodName) {
                if (! $reflection->hasMethod($methodName)) {
                    continue;
                }

                $method = $reflection->getMethod($methodName);
                if (! $method->isPublic() || $method->getDeclaringClass()->getName() !== $class) {
                    continue;
                }

                foreach ($method->getParameters() as $parameter) {
                    if ($parameter->getName() === 'csrfToken') {
                        $violations[] = $class . '::' . $methodName . '($csrfToken)';
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "CSRF is supplied through ResourceObject uri query, not Resource signatures:\n" . implode("\n", $violations),
        );
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
