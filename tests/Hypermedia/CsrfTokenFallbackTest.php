<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function file_get_contents;
use function implode;
use function preg_match;
use function sort;
use function sprintf;
use function str_contains;
use function substr;

/**
 * Regression guard: every rendered CSRF token field must survive a re-render.
 *
 * A write handler (onPost/onPut) does NOT always re-issue `csrfToken` in its
 * response body — e.g. Admin/Order::onPut returns the updated order with no
 * `csrfToken` key, yet the html context re-renders the SAME form template with
 * that body. A template that prints the bare `{{ csrfToken }}` then renders an
 * EMPTY hidden field, so the user's NEXT save submits no token and gets a
 * "403 Forbidden — CSRF token missing." (Admin/Order, Admin/Product and
 * Admin/ProductNew all shipped this bug.)
 *
 * The fix — used by ~80 other templates — is the self-issuing fallback
 * `{{ csrfToken|default(csrf_token()) }}`: when the body carries no token, the
 * `csrf_token()` Twig function issues a fresh valid one against the session. So
 * a csrfToken hidden input must NEVER print the bare `{{ csrfToken }}`.
 */
final class CsrfTokenFallbackTest extends TestCase
{
    private const TEMPLATES_DIR = __DIR__ . '/../../var/templates';

    public function testNoCsrfTokenFieldPrintsTheBareUnfallbackedValue(): void
    {
        $offenders = [];
        foreach ($this->twigFiles() as $file) {
            $contents = (string) file_get_contents($file);
            // A hidden csrfToken input that prints {{ csrfToken }} with no
            // |default(csrf_token()) fallback — empty on a body-less re-render.
            if (preg_match('/name="csrfToken"\s+value="\{\{\s*csrfToken\s*\}\}"/', $contents) === 1) {
                $offenders[] = substr($file, strlen(self::TEMPLATES_DIR) + 1);
            }
        }

        sort($offenders);

        self::assertSame(
            [],
            $offenders,
            sprintf(
                "Template(s) print a bare {{ csrfToken }} CSRF field — it renders EMPTY when the write "
                . "handler re-renders without a csrfToken in its body, so the next submit 403s "
                . "(CSRF token missing). Use {{ csrfToken|default(csrf_token()) }}:\n  - %s",
                implode("\n  - ", $offenders),
            ),
        );
    }

    /** @return list<string> */
    private function twigFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::TEMPLATES_DIR, FilesystemIterator::SKIP_DOTS),
        );
        $files = [];
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (str_contains($file->getFilename(), '.html.twig')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
