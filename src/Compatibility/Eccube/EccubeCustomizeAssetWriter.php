<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Reason\Service\CustomizeAssetWriterInterface;
use Override;

/**
 * EC-CUBE-compatible customize CSS/JS boundary.
 *
 * Holds the customize-CSS/JS bodies in process (bound as a singleton) so
 * `doUpdateContentCss` / `doUpdateContentJs` are exercisable end to end.
 * Writing the real `customize.css` / `customize.js` files under the public
 * asset path is the production cutover residual (migration-status §4).
 */
final class EccubeCustomizeAssetWriter implements CustomizeAssetWriterInterface
{
    private string $css = '';
    private string $js = '';

    #[Override]
    public function writeCss(string $css): void
    {
        $this->css = $css;
    }

    #[Override]
    public function writeJs(string $js): void
    {
        $this->js = $js;
    }

    #[Override]
    public function readCss(): string
    {
        return $this->css;
    }

    #[Override]
    public function readJs(): string
    {
        return $this->js;
    }
}
