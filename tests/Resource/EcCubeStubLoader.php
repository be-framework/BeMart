<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use Twig\Loader\LoaderInterface;
use Twig\Source;

use function file_exists;
use function file_get_contents;
use function in_array;
use function preg_replace;
use function str_contains;

/**
 * Twig loader for the EC-CUBE 4.3 reference render in
 * {@see CartHtmlRenderTest}.
 *
 * Serves EC-CUBE's REAL `Cart/index.twig` and `default_frame.twig` from
 * the gitignored 4.3 clone — those are the templates under test. Every
 * other include EC-CUBE's frame pulls (`meta.twig`, `block.twig`,
 * `snippet.twig`, `@common/lang.twig`, `@admin/snippet.twig`) is served
 * as an EMPTY template: those are configurable layout-block / plugin /
 * SEO-meta fragments with no BeMart equivalent, so they contribute
 * nothing on either side of the diff (BeMart's base.html.twig leaves the
 * same regions empty). This keeps the comparison focused on the cart
 * page + frame skeleton that were actually ported.
 *
 * The storefront form pages (Entry / Contact / ProductList) use the
 * Symfony twig-bridge `{% form_theme %}` tag, which is not installed in
 * this repo (no symfony/form, no symfony/twig-bridge). `form_theme` only
 * SELECTS the form theme; with the `form_widget` / `form_label` /
 * `form_errors` helpers stubbed to deterministic markers, the tag is a
 * pure no-op. So the loader strips the `{% form_theme ... %}` tag from
 * the source before handing it to Twig — there is nothing for it to do.
 */
final class EcCubeStubLoader implements LoaderInterface
{
    /** Includes with no BeMart equivalent — served empty. */
    private const STUBBED_EMPTY = [
        'meta.twig',
        'block.twig',
        'snippet.twig',
    ];

    public function __construct(private readonly string $templateRoot)
    {
    }

    public function getSourceContext(string $name): Source
    {
        if ($this->isStubbed($name)) {
            return new Source('', $name);
        }

        $path = $this->templateRoot . '/' . $name;
        $source = (string) file_get_contents($path);

        // `{% form_theme ... %}` is a Symfony twig-bridge tag (not
        // installed). It only selects a form theme; with the form_*
        // helpers stubbed it is a no-op, so strip it so Twig can parse.
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
        if ($this->isStubbed($name)) {
            return true;
        }

        return file_exists($this->templateRoot . '/' . $name);
    }

    private function isStubbed(string $name): bool
    {
        // Namespaced includes (@common/lang.twig, @admin/snippet.twig)
        // and the plain configurable-block fragments.
        if (str_contains($name, '@')) {
            return true;
        }

        return in_array($name, self::STUBBED_EMPTY, true);
    }
}
