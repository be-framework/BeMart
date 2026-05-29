<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Customize CSS/JS boundary (`doUpdateContentCss` / `doUpdateContentJs`).
 *
 * EC-CUBE's `CssController` / `JsController` read and write a single
 * `customize.css` / `customize.js` file under the public asset path. That
 * public-file side-effect stays behind this boundary; the Be Finals
 * depend only on this interface.
 */
interface CustomizeAssetWriterInterface
{
    public function writeCss(string $css): void;

    public function writeJs(string $js): void;

    public function readCss(): string;

    public function readJs(): string;
}
