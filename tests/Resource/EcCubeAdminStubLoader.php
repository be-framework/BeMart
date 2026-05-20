<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use Twig\Loader\LoaderInterface;
use Twig\Source;

use function file_exists;
use function file_get_contents;
use function in_array;
use function preg_replace;
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
 *  - serves the page template + `@admin/default_frame.twig` +
 *    `@admin/nav.twig` for real — the admin layout being verified;
 *  - serves the remaining admin includes EMPTY: `alert.twig`,
 *    `info.twig`, `notice_debug_mode.twig`, `snippet.twig`,
 *    `pager.twig`, `@common/lang.twig`. Those are flash-message / notice
 *    / plugin / pager / JS-i18n fragments with no BeMart equivalent;
 *    BeMart's `admin-base.html.twig` likewise omits them, so serving
 *    them empty keeps the diff focused on the frame skeleton + nav + the
 *    page content that were actually ported.
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

        $path = $this->adminTemplateRoot . '/' . $relative;
        $source = (string) file_get_contents($path);
        $source = (string) preg_replace('/\{%-?\s*form_theme\b.*?-?%\}/s', '', $source);

        return new Source($source, $name, $path);
    }

    public function getCacheKey(string $name): string
    {
        return $name;
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
}
