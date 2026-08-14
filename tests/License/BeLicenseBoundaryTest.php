<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\License;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_values;
use function count;
use function file_get_contents;
use function glob;
use function in_array;
use function json_decode;
use function mb_strlen;
use function preg_match;
use function preg_match_all;
use function sort;
use function sprintf;

use const JSON_THROW_ON_ERROR;
use const PREG_SET_ORDER;

/**
 * The repository is GPL-2.0-only because it carries EC-CUBE expression;
 * `be/` is MIT because it does not. That is a measured claim, and a stray
 * copy-paste would silently make it false.
 *
 * The EC-CUBE Japanese UI text lives in `tests/Resource/Admin/*JaMessages.php`,
 * whose docblocks state it was copied verbatim from
 * `src/Eccube/Resource/locale/messages.ja.yaml`. That file set is the
 * dictionary here: no sentence from it may appear anywhere under `be/`.
 *
 * Short shared vocabulary (`注文番号`, `支払方法`) is not protectable and is
 * expected on both sides, so only sentence-length strings are compared.
 */
final class BeLicenseBoundaryTest extends TestCase
{
    /** Below this length a match is shared vocabulary, not copied expression. */
    private const SENTENCE_LENGTH = 12;

    public function testBePackageCarriesNoEcCubeSentence(): void
    {
        $dictionary = $this->ecCubeSentences();
        $found = [];
        foreach ($this->beFiles() as $file) {
            foreach ($this->japaneseLiterals($file->getPathname()) as $literal) {
                if (! in_array($literal, $dictionary, true)) {
                    continue;
                }

                $found[] = sprintf('%s: %s', $file->getFilename(), $literal);
            }
        }

        sort($found);

        $this->assertSame([], $found);
    }

    /** A dictionary that stopped extracting would make the test above vacuous. */
    public function testEcCubeSentenceDictionaryIsPopulated(): void
    {
        $this->assertGreaterThan(100, count($this->ecCubeSentences()));
    }

    public function testBePackageDeclaresMit(): void
    {
        $root = __DIR__ . '/../..';
        $this->assertFileExists($root . '/be/LICENSE');

        $composer = json_decode(
            (string) file_get_contents($root . '/be/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertIsArray($composer);
        $this->assertSame('MIT', $composer['license']);
    }

    /**
     * Sentence-length values copied verbatim from EC-CUBE's ja catalogue.
     *
     * @return list<string>
     */
    private function ecCubeSentences(): array
    {
        $sentences = [];
        foreach (glob(__DIR__ . '/../Resource/Admin/*JaMessages.php') ?: [] as $file) {
            $contents = (string) file_get_contents($file);
            preg_match_all("/=>\s*'((?:[^'\\\\]|\\\\.)*)'/", $contents, $matches, PREG_SET_ORDER);
            foreach ($matches as [, $value]) {
                if (! $this->isSentence($value)) {
                    continue;
                }

                $sentences[] = $value;
            }
        }

        return array_values($sentences);
    }

    /** @return list<string> */
    private function japaneseLiterals(string $file): array
    {
        $contents = (string) file_get_contents($file);
        preg_match_all(
            "/'((?:[^'\\\\]|\\\\.)*)'|\"((?:[^\"\\\\]|\\\\.)*)\"/",
            $contents,
            $matches,
            PREG_SET_ORDER,
        );
        $literals = [];
        foreach ($matches as $match) {
            $value = $match[1] !== '' ? $match[1] : ($match[2] ?? '');
            if (! $this->isSentence($value)) {
                continue;
            }

            $literals[] = $value;
        }

        return $literals;
    }

    private function isSentence(string $value): bool
    {
        if (mb_strlen($value) < self::SENTENCE_LENGTH) {
            return false;
        }

        return preg_match('/[\x{3040}-\x{30ff}\x{4e00}-\x{9faf}]/u', $value) === 1;
    }

    /** PHP and JSON under `be/` — sources, tests, fixtures and analysis notes alike. */
    private function beFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/../../be'),
        );
        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            if (in_array($file->getExtension(), ['php', 'json'], true)) {
                $files[] = $file;
            }
        }

        return $files;
    }
}
