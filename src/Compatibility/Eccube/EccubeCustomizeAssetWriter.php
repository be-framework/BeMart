<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Reason\Service\CustomizeAssetWriterInterface;
use Override;
use Ray\Di\Di\Named;

use function dirname;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_dir;
use function is_file;
use function is_string;
use function json_decode;
use function json_encode;
use function mkdir;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * EC-CUBE-compatible customize CSS/JS boundary.
 *
 * BeMart keeps the edited bodies in a runtime file keyed by DATABASE_URL so
 * HTTP/browser readback observes the same state across separate requests. The
 * production cutover residual is still writing EC-CUBE's public
 * `customize.css` / `customize.js` asset files.
 */
final class EccubeCustomizeAssetWriter implements CustomizeAssetWriterInterface
{
    private readonly string $stateFile;

    public function __construct(
        #[Named('databaseCacheSuffix')]
        string $databaseCacheSuffix = 'default',
        string|null $stateFile = null,
    ) {
        $this->stateFile = $stateFile
            ?? dirname(__DIR__, 3) . '/var/tmp/customize-assets-' . $databaseCacheSuffix . '.json';
    }

    #[Override]
    public function writeCss(string $css): void
    {
        $state = $this->readState();
        $state['css'] = $css;
        $this->writeState($state);
    }

    #[Override]
    public function writeJs(string $js): void
    {
        $state = $this->readState();
        $state['js'] = $js;
        $this->writeState($state);
    }

    #[Override]
    public function readCss(): string
    {
        return $this->readState()['css'];
    }

    #[Override]
    public function readJs(): string
    {
        return $this->readState()['js'];
    }

    /** @return array{css: string, js: string} */
    private function readState(): array
    {
        if (! is_file($this->stateFile)) {
            return ['css' => '', 'js' => ''];
        }

        $decoded = json_decode((string) file_get_contents($this->stateFile), true);
        if (! is_array($decoded)) {
            return ['css' => '', 'js' => ''];
        }

        $css = $decoded['css'] ?? '';
        $js = $decoded['js'] ?? '';

        return [
            'css' => is_string($css) ? $css : '',
            'js' => is_string($js) ? $js : '',
        ];
    }

    /** @param array{css: string, js: string} $state */
    private function writeState(array $state): void
    {
        $directory = dirname($this->stateFile);
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents(
            $this->stateFile,
            json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n",
        );
    }
}
