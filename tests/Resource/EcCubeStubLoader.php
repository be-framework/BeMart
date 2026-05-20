<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use Twig\Loader\LoaderInterface;
use Twig\Source;

use function file_exists;
use function file_get_contents;
use function in_array;
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

        return new Source((string) file_get_contents($path), $name, $path);
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
