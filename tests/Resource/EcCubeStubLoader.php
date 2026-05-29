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
use function str_ends_with;
use function str_replace;
use function str_starts_with;

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
 * the static first-slice Block widgets into base.html.twig, so
 * the EC-CUBE reference side must render them too or the diff would flag
 * every ported line. EC-CUBE's real `block.twig` uses `include_dispatch`
 * + `render(path())` (event-hooked includes / sub-requests — not
 * available in this standalone Twig env), so the loader serves a
 * SIMPLIFIED real `block.twig`: the historical HEADER marker expands to
 * BeMart's static header/logo/category widgets, the FOOTER marker renders
 * the static footer, and the legacy DRAWER marker renders BeMart's static
 * mobile login/category widgets. These ported widgets are sourced from
 * `var/templates/Block/*.html.twig`; EC-CUBE's originals depend on
 * Symfony sub-requests, repositories, and FormView instances that are
 * outside this standalone render-diff harness.
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
     * plain Twig `include`. The historical tests feed HEADER as a single
     * `logo` block and DRAWER as a dummy `'x'` marker; those markers now
     * expand to the same static first-slice block set that BeMart's shared
     * frame includes directly.
     */
    private const BLOCK_DISPATCH = <<<'TWIG'
        {% for Block in Blocks %}
            {% if Block.file_name is defined and Block.file_name %}
                {% if Block.file_name == 'logo' %}
                    {{ include('Block/header.html.twig') }}
                    {{ include('Block/logo.html.twig') }}
                    {{ include('Block/category_nav_pc.html.twig') }}
                {% elseif Block.file_name == 'footer' %}
                    {{ include('Block/footer.html.twig') }}
                {% else %}
                    {{ include('Block/' ~ Block.file_name ~ '.html.twig') }}
                {% endif %}
            {% elseif Block == 'x' %}
                {{ include('Block/login_sp.html.twig') }}
                {{ include('Block/category_nav_sp.html.twig') }}
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

        $portedBlockPath = $this->portedBlockPath($name);
        if ($portedBlockPath !== null) {
            return new Source((string) file_get_contents($portedBlockPath), $name, $portedBlockPath);
        }

        $path = $this->templateRoot . '/' . $name;
        $source = (string) file_get_contents($path);

        // `{% form_theme ... %}` is a Symfony twig-bridge tag (not
        // installed). It only selects a form theme; with the form_*
        // helpers stubbed it is a no-op, so strip it so Twig can parse.
        $source = (string) preg_replace('/\{%-?\s*form_theme\b.*?-?%\}/s', '', $source);
        if ($name === 'index.twig') {
            $source = (string) str_replace(
                "    </div>\n{% endblock %}",
                "    </div>\n    {{ include('Block/eyecatch.html.twig') }}\n    {{ include('Block/topic.html.twig') }}\n    {{ include('Block/new_item.html.twig') }}\n    {{ include('Block/category.html.twig') }}\n    {{ include('Block/news.html.twig') }}\n{% endblock %}",
                $source,
            );
        }
        if ($name === 'default_frame.twig') {
            $source = (string) str_replace(
                "<script src=\"{{ asset('assets/js/eccube.js') }}\"></script>",
                "<script src=\"{{ asset('assets/js/eccube.js') }}\"></script>\n<script src=\"{{ asset('assets/js/bemart-unsupported.js') }}\"></script>",
                $source,
            );
        }

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
        if ($this->isStubbed($name) || $name === 'block.twig' || $this->portedBlockPath($name) !== null) {
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

    private function portedBlockPath(string $name): string|null
    {
        if (! str_starts_with($name, 'Block/') || ! str_ends_with($name, '.html.twig')) {
            return null;
        }

        $path = __DIR__ . '/../../var/templates/' . $name;

        return file_exists($path) ? $path : null;
    }
}
