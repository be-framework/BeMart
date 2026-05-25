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
 * other include EC-CUBE's frame pulls (`meta.twig`, `snippet.twig`,
 * `@common/lang.twig`, `@admin/snippet.twig`) is served as an EMPTY
 * template: those are plugin / SEO-meta fragments with no BeMart
 * equivalent, so they contribute nothing on either side of the diff.
 *
 * `block.twig` is the storefront layout-block dispatcher. BeMart ports
 * the purely-static Block widgets (logo, footer) into base.html.twig, so
 * the EC-CUBE reference side must render them too or the diff would flag
 * every ported line. EC-CUBE's real `block.twig` uses `include_dispatch`
 * + `render(path())` (event-hooked includes / sub-requests — not
 * available in this standalone Twig env), so the loader serves a
 * SIMPLIFIED real `block.twig`: it iterates the region's Block list and
 * plain-`include`s `Block/{file_name}.twig` for each entry that carries a
 * `file_name`. A region whose Block list holds only the legacy dummy
 * `'x'` marker (no `file_name`) renders nothing — matching BeMart's
 * frame, which leaves the un-ported regions (DRAWER, the data-bearing
 * HEADER widgets) empty. The real `Block/logo.twig` / `Block/footer.twig`
 * are then served straight from the clone — those ARE the port reference.
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
        'snippet.twig',
    ];

    /**
     * Simplified storefront block dispatcher served in place of EC-CUBE's
     * real `block.twig`.
     *
     * EC-CUBE's `block.twig` iterates a region's Block list and renders
     * each widget via `include_dispatch` / `render(path())` — event-hooked
     * includes and sub-requests that have no equivalent in this standalone
     * Twig env. This replacement keeps the dispatch contract but uses a
     * plain Twig `include`: for every Block entry that carries a
     * `file_name` it includes `Block/{file_name}.twig` (the real EC-CUBE
     * widget, served from the clone). A list holding only the legacy dummy
     * `'x'` marker (a plain string, no `file_name`) renders nothing — so
     * an un-ported region (DRAWER, the data-bearing HEADER widgets) stays
     * empty on the EC-CUBE side, matching BeMart's frame.
     *
     * The widget is included with an explicit `BaseInfo.shop_name` of
     * `BeMart`: the ported `logo` / `footer` widgets read the shop name,
     * and BeMart's storefront frame has no BaseInfo entity so it always
     * falls back to `BeMart`. Injecting the same name here — scoped to the
     * widget include only, leaving each test's page-level `BaseInfo`
     * untouched (e.g. Help/About's deliberately-empty BaseInfo guards) —
     * makes the ported widgets diff to zero instead of surfacing a
     * shop-name residual.
     */
    private const BLOCK_DISPATCH = <<<'TWIG'
        {% for Block in Blocks %}
            {% if Block.file_name is defined and Block.file_name %}
                {{ include('Block/' ~ Block.file_name ~ '.twig', { BaseInfo: { shop_name: 'BeMart' } }) }}
            {% endif %}
        {% endfor %}
        TWIG;

    public function __construct(private readonly string $templateRoot)
    {
    }

    public function getSourceContext(string $name): Source
    {
        if ($this->isStubbed($name)) {
            return new Source('', $name);
        }

        if ($name === 'block.twig') {
            return new Source(self::BLOCK_DISPATCH, $name);
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
        if ($this->isStubbed($name) || $name === 'block.twig') {
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
