<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\CustomizeAssetWriterInterface;
use Override;

/** Recording fake for the customize CSS/JS boundary. */
final class FakeCustomizeAssetWriter implements CustomizeAssetWriterInterface
{
    public string $css = '';
    public string $js = '';

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
