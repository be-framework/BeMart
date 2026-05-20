<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

/**
 * Empty flash bag for the EC-CUBE Cart template render in
 * {@see CartHtmlRenderTest}.
 *
 * EC-CUBE's Cart/index.twig reads `app.session.flashbag.get(...)` for
 * request-scoped error messages. A bare cart render has no flash errors;
 * this stub returns an empty list for every key so the error `{% for %}`
 * loops produce nothing — matching BeMart, which has no flash layer.
 */
final class EcCubeFlashBag
{
    /** @return list<mixed> */
    public function get(string $key): array
    {
        return [];
    }
}
