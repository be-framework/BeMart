<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use Madapaja\TwigModule\TwigModule;
use Override;

/**
 * HTML presentation context (Phase 3 Step 1 — Twig rendering pilot).
 *
 * Composes AppModule (the dev-default Fake-backed bindings) and swaps the
 * resource renderer: where `app` / `prod` bind a JSON-shaped renderer,
 * `html` binds madapaja/twig-module's TwigRenderer to RenderInterface.
 *
 * Why a dedicated context (and not content-negotiation):
 * - The 1422 existing tests assert on `$ro->body` (PHP arrays) and never
 *   touch RenderInterface, so they are renderer-agnostic. JSON contexts
 *   (`app`, `prod`) keep their renderer untouched — HTML is purely
 *   additive.
 * - A context module is the idiomatic BEAR.Sunday seam for "same
 *   resources, different representation": `bin/app.php` / `public/index.php`
 *   already resolve `APP_CONTEXT=foo` to `FooModule`, so `APP_CONTEXT=html`
 *   selects this module with zero entry-point branching for module choice.
 *
 * Template convention (TwigRenderer + TemplateFinder):
 *   src/Resource/Page/Cart.php  ->  var/templates/Page/Cart.html.twig
 * TwigModule with empty $paths falls back to AppPathProvider, which
 * registers both `src/Resource` and `var/templates` as Twig roots; the
 * TemplateFinder strips everything up to and including `/Resource/` from
 * the resource class file and swaps `.php` for `.html.twig`. `$ro->body`
 * is passed as the template context (the renderer also injects `_ro`).
 *
 * Remaining ~138 pages are mechanical: one `var/templates/<path>.html.twig`
 * per resource, no module or wiring changes.
 */
final class HtmlModule extends AbstractAppModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new AppModule($this->appMeta));

        // TwigModule binds RenderInterface -> TwigRenderer. `override()`
        // makes it win over the JSON renderer that PackageModule installed
        // through AppModule.
        $this->override(new TwigModule());
    }
}
