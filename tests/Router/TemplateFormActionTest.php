<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Router;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_diff;
use function array_unique;
use function array_values;
use function explode;
use function file_get_contents;
use function implode;
use function is_file;
use function preg_match;
use function preg_match_all;
use function sort;
use function sprintf;
use function str_contains;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function trim;
use function ucwords;

use const PREG_SET_ORDER;

/**
 * A template that renders `<form method="post">` against a resource with no
 * write method is a dead button: submitting it answers 405. EC-CUBE ports the
 * markup first, so the form can outrun its handler.
 *
 * {@see self::KNOWN_DEAD} is that list as it stands. A new dead form fails
 * here, and a fixed one fails too until it leaves the list.
 */
final class TemplateFormActionTest extends TestCase
{
    /**
     * Resources rendering a POST form with no onPost/onPut/onDelete/onPatch.
     * Verified with `composer page -- post <path>`: every one answers 405.
     */
    private const KNOWN_DEAD = [
        'src/Resource/Page/Admin/Content/FileManager.php',
        'src/Resource/Page/Admin/Customer.php',
        'src/Resource/Page/Admin/CustomerDeliveryEdit.php',
        'src/Resource/Page/Admin/Product/CsvProduct.php',
        'src/Resource/Page/Admin/Product/ProductClass.php',
        'src/Resource/Page/Admin/TwoFactorAuthEdit.php',
    ];

    public function testPostFormsReachAResourceWithAWriteMethod(): void
    {
        ['dead' => $dead] = $this->scan();

        $this->assertSame([], array_values(array_diff($dead, self::KNOWN_DEAD)));
    }

    public function testKnownDeadFormListHasNoStaleEntry(): void
    {
        ['dead' => $dead] = $this->scan();

        $this->assertSame([], array_values(array_diff(self::KNOWN_DEAD, $dead)));
    }

    public function testEveryPostFormActionResolvesToAResource(): void
    {
        ['unresolved' => $unresolved] = $this->scan();

        $this->assertSame([], $unresolved);
    }

    /** @return array{dead: list<string>, unresolved: list<string>} */
    private function scan(): array
    {
        $root = __DIR__ . '/../..';
        $dead = [];
        $unresolved = [];
        foreach ($this->twigFiles() as $file) {
            $contents = (string) file_get_contents($file->getPathname());
            preg_match_all('/<form\b[^>]*>/s', $contents, $tags, PREG_SET_ORDER);
            foreach ($tags as [$tag]) {
                if (! $this->isPost($tag)) {
                    continue;
                }

                foreach ($this->actionPaths($tag) as $path) {
                    $relative = $this->resourceFile($path);
                    if (! is_file($root . '/' . $relative)) {
                        $unresolved[] = sprintf('%s -> %s', $path, $relative);

                        continue;
                    }

                    if ($this->hasWriteMethod($root . '/' . $relative)) {
                        continue;
                    }

                    $dead[] = $relative;
                }
            }
        }

        return ['dead' => $this->normalize($dead), 'unresolved' => $this->normalize($unresolved)];
    }

    private function isPost(string $tag): bool
    {
        if (preg_match('/\bmethod\s*=\s*"([^"]*)"/i', $tag, $matches) !== 1) {
            return false;
        }

        return strtolower(trim($matches[1])) === 'post';
    }

    /**
     * An action is either a literal path with Twig noise appended
     * (`/admin/layout/layout{% if … %}?…{% endif %}`) or an expression that
     * picks between paths (`{{ isEdit ? '/a' : '/b' }}`). The first form is
     * the static prefix; the second submits to every branch literal.
     *
     * @return list<string>
     */
    private function actionPaths(string $tag): array
    {
        if (preg_match('/\baction\s*=\s*"([^"]*)"/i', $tag, $matches) !== 1) {
            return [];
        }

        $action = trim($matches[1]);
        $prefix = $this->staticPrefix($action);
        if ($prefix !== '') {
            return $this->isRoutable($prefix) ? [$prefix] : [];
        }

        preg_match_all("/'([^']*)'/", $action, $literals, PREG_SET_ORDER);
        $paths = [];
        foreach ($literals as [, $literal]) {
            $candidate = $this->staticPrefix(trim($literal));
            if (! $this->isRoutable($candidate)) {
                continue;
            }

            $paths[] = $candidate;
        }

        return $paths;
    }

    /** The path stops at the query string or at the first Twig tag. */
    private function staticPrefix(string $action): string
    {
        $end = strlen($action);
        foreach (['?', '{{', '{%'] as $marker) {
            $at = strpos($action, $marker);
            if ($at !== false && $at < $end) {
                $end = $at;
            }
        }

        return substr($action, 0, $end);
    }

    /** An empty, fragment-only, or absolute action does not name a local resource. */
    private function isRoutable(string $path): bool
    {
        return $path !== '' && $path[0] === '/' && ! str_contains($path, '://');
    }

    /** `/admin/customer-delivery-edit` -> `src/Resource/Page/Admin/CustomerDeliveryEdit.php` */
    private function resourceFile(string $path): string
    {
        $segments = [];
        foreach (explode('/', trim($path, '/')) as $segment) {
            if ($segment === '') {
                continue;
            }

            $segments[] = str_replace(' ', '', ucwords(str_replace('-', ' ', $segment)));
        }

        if ($segments === []) {
            $segments = ['Index'];
        }

        return 'src/Resource/Page/' . implode('/', $segments) . '.php';
    }

    private function hasWriteMethod(string $file): bool
    {
        $contents = (string) file_get_contents($file);

        return preg_match('/public function on(?:Post|Put|Delete|Patch)\s*\(/', $contents) === 1;
    }

    /**
     * @param list<string> $values
     *
     * @return list<string>
     */
    private function normalize(array $values): array
    {
        $unique = array_values(array_unique($values));
        sort($unique);

        return $unique;
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
