<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use Twig\Loader\LoaderInterface;
use Twig\Source;

use function file_exists;
use function file_get_contents;
use function in_array;
use function preg_match;
use function preg_replace;
use function str_replace;
use function str_starts_with;
use function substr;

/**
 * Twig loader for the EC-CUBE 4.3 ADMIN reference render — the admin-HTML
 * counterpart of {@see EcCubeStubLoader}.
 *
 * The storefront render-diff tests stub EVERY namespaced (`@`-prefixed)
 * include as empty. That cannot work for the admin pages: an admin page
 * does `{% extends '@admin/default_frame.twig' %}`, and the admin frame
 * `{{ include('@admin/nav.twig') }}`s the sidebar — those two ARE the
 * admin layout under test, so they must be served for real.
 *
 * This loader therefore:
 *
 *  - serves `@admin/<file>` and `@common/<file>` from the EC-CUBE admin
 *    template root (the `@admin` namespace maps to
 *    `template/admin/`, `@common` to `template/admin/` too — the only
 *    `@common` include is `lang.twig`, stubbed empty below);
 *  - serves the page template + `@admin/default_frame.twig` for real;
 *  - serves `@admin/nav.twig` from BeMart's static admin nav port, because
 *    the current BeMart frame intentionally inlines the first-slice menu
 *    instead of carrying EC-CUBE's dynamic `eccubeNav` runtime tree;
 *  - serves the remaining admin includes EMPTY: `alert.twig`,
 *    `info.twig`, `notice_debug_mode.twig`, `snippet.twig`,
 *    `pager.twig`, `search_items.twig`, `@common/lang.twig`. Those are
 *    flash-message / notice / plugin / pager / saved-search-chips /
 *    JS-i18n fragments with no BeMart equivalent; BeMart's
 *    `admin-base.html.twig` and the list-page ports likewise omit them,
 *    so serving them empty keeps the diff focused on the frame skeleton
 *    + nav + the page content that were actually ported.
 *
 * As in {@see EcCubeStubLoader}, the Symfony twig-bridge `{% form_theme %}`
 * tag is stripped from the source (not installed; a no-op once the
 * `form_*` helpers are stubbed).
 */
final class EcCubeAdminStubLoader implements LoaderInterface
{
    /**
     * Admin includes with no BeMart equivalent — served empty. Matched
     * by the basename after the `@admin/` / `@common/` namespace prefix.
     */
    private const STUBBED_EMPTY = [
        'alert.twig',
        'info.twig',
        'notice_debug_mode.twig',
        'snippet.twig',
        'pager.twig',
        'search_items.twig',
        'lang.twig',
    ];

    public function __construct(private readonly string $adminTemplateRoot)
    {
    }

    public function getSourceContext(string $name): Source
    {
        $relative = $this->relative($name);

        if ($this->isStubbed($relative)) {
            return new Source('', $name);
        }

        if ($relative === 'nav.twig') {
            return new Source($this->portedAdminNav(), $name);
        }

        $path = $this->adminTemplateRoot . '/' . $relative;
        $source = (string) file_get_contents($path);
        $source = (string) preg_replace('/\{%-?\s*form_theme\b.*?-?%\}/s', '', $source);
        if ($relative === 'default_frame.twig' || $relative === 'login_frame.twig') {
            $source = (string) str_replace(
                "<script src=\"{{ asset('assets/js/function.js', 'admin') }}\"></script>",
                "<script src=\"{{ asset('assets/js/function.js', 'admin') }}\"></script>\n<script src=\"{{ asset('assets/js/bemart-unsupported.js') }}\"></script>",
                $source,
            );
        }
        $source = $this->normalizeAdminBranding($source);

        return new Source($source, $name, $path);
    }

    public function getCacheKey(string $name): string
    {
        // Namespace the cache key so admin templates compile to a
        // DIFFERENT Twig class than an identically-named default-theme
        // template loaded by {@see EcCubeStubLoader}. Twig derives the
        // compiled-class name from `getCacheKey()` (see
        // Environment::getTemplateClass), and a process-wide PHP class is
        // reused once declared — so without a namespace, the admin
        // `index.twig` / `default_frame.twig` and the storefront ones of
        // the same bare name would collide, and whichever test compiled
        // first would feed its template to the other. The prefix keeps
        // the two render-diff suites isolated regardless of run order.
        return '@admin-stub/' . $name;
    }

    public function isFresh(string $name, int $time): bool
    {
        return true;
    }

    public function exists(string $name): bool
    {
        $relative = $this->relative($name);
        if ($this->isStubbed($relative)) {
            return true;
        }

        return file_exists($this->adminTemplateRoot . '/' . $relative);
    }

    /**
     * Strips the `@admin/` / `@common/` namespace prefix; a bare name is
     * returned unchanged (the page template is loaded by bare path).
     */
    private function relative(string $name): string
    {
        if (str_starts_with($name, '@admin/')) {
            return substr($name, 7);
        }

        if (str_starts_with($name, '@common/')) {
            return substr($name, 8);
        }

        return $name;
    }

    private function isStubbed(string $relative): bool
    {
        return in_array($relative, self::STUBBED_EMPTY, true);
    }

    private function portedAdminNav(): string
    {
        $source = (string) file_get_contents(__DIR__ . '/../../var/templates/admin-base.html.twig');
        if (preg_match('/<nav>.*?<\/nav>/s', $source, $matches) === 1) {
            return $matches[0];
        }

        return '';
    }

    private function normalizeAdminBranding(string $source): string
    {
        return str_replace(
            [
                "<h1><img src=\"{{ asset('assets/img/logo@2x.png', 'admin') }}\"></h1>",
                "<p><img src=\"{{ asset('assets/img/logo2.png', 'admin') }}\" width=\"106\"></p>",
                '<small>Copyright &copy; 2000-{{ "now"|date("Y") }} EC-CUBE CO.,LTD. All Rights Reserved.</small>',
            ],
            [
                "<h1><img src=\"{{ asset('assets/img/logo@2x.png', 'admin') }}\" alt=\"BeMart\"></h1>",
                "<p><img src=\"{{ asset('assets/img/logo2.png', 'admin') }}\" alt=\"BeMart\" width=\"106\"></p>",
                '<small>Copyright &copy; 2026 BeMart. All Rights Reserved.</small>',
            ],
            $source,
        );
    }
}
