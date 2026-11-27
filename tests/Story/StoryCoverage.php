<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use function array_diff;
use function array_filter;
use function array_keys;
use function array_unique;
use function array_values;
use function assert;
use function count;
use function dirname;
use function explode;
use function file_exists;
use function file_get_contents;
use function in_array;
use function is_array;
use function json_decode;
use function ksort;
use function preg_match_all;
use function preg_replace;
use function sort;
use function sprintf;
use function str_starts_with;
use function strlen;
use function strstr;
use function strtolower;
use function trim;

use const JSON_THROW_ON_ERROR;

/**
 * ALPS の遷移の `link`（rel=story）と、ストーリーが辿る rel の突き合わせ。
 *
 * `followable()` の行は golden を持つので、辿る遷移の `link` に golden への href を 1 行ずつ足す。
 */
final class StoryCoverage
{
    public const REL = 'story';

    private const GOLDEN_DIR = 'tests/Story/golden/';
    private const TRANSITION_TYPES = ['safe', 'unsafe', 'idempotent'];

    /** @var array<string, array{flows: list<string>, stories: list<string>}> */
    private array $transitions = [];

    public function __construct(private readonly string $root)
    {
        $alps = json_decode((string) file_get_contents($root . '/alps.json'), true, 512, JSON_THROW_ON_ERROR);
        assert(is_array($alps) && is_array($alps['alps']['descriptor'] ?? null));
        foreach ($alps['alps']['descriptor'] as $descriptor) {
            if (! in_array($descriptor['type'] ?? null, self::TRANSITION_TYPES, true)) {
                continue;
            }

            $tags = explode(' ', (string) ($descriptor['tag'] ?? ''));
            $stories = [];
            foreach ($descriptor['link'] ?? [] as $link) {
                if (($link['rel'] ?? null) === self::REL) {
                    $stories[] = (string) $link['href'];
                }
            }

            $this->transitions[(string) $descriptor['id']] = [
                'flows' => array_values(array_filter($tags, static fn (string $tag): bool => str_starts_with($tag, 'flow-'))),
                'stories' => $stories,
            ];
        }
    }

    public static function forRepository(): self
    {
        return new self(dirname(__DIR__, 2));
    }

    /**
     * ストーリーごとに辿る rel。キーは遷移の id、値は golden の href（`link` に書く形）。
     *
     * @return array<string, list<string>>
     */
    public function expectedLinks(): array
    {
        $expected = [];
        foreach (LinkStories::followable() as $name => $story) {
            $href = self::GOLDEN_DIR . trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-') . '.stree.txt';
            foreach ($this->relsOf($story[0]) as $rel) {
                $expected[$rel][] = $href;
            }
        }

        ksort($expected);

        return $expected;
    }

    /**
     * golden を持たない行（uploads・persisted）だけが辿る遷移。`link` の先がないので、突き合わせの対象にしない。
     *
     * @return list<string>
     */
    public function followedWithoutGolden(): array
    {
        $withoutGolden = [];
        foreach ([LinkStories::uploads(), LinkStories::persisted()] as $stories) {
            foreach ($stories as $story) {
                $withoutGolden = [...$withoutGolden, ...$this->relsOf($story[0])];
            }
        }

        $withoutGolden = array_values(array_diff(array_unique($withoutGolden), array_keys($this->expectedLinks())));
        sort($withoutGolden);

        return $withoutGolden;
    }

    /**
     * ストーリーに辿られていない遷移を flow ごとにまとめる。flow タグがないものは `(no flow)`。
     *
     * @return array<string, list<string>>
     */
    public function uncovered(): array
    {
        $followed = array_keys($this->expectedLinks());
        $byFlow = [];
        foreach ($this->transitions as $id => $transition) {
            if (in_array($id, $followed, true)) {
                continue;
            }

            foreach ($transition['flows'] ?: ['(no flow)'] as $flow) {
                $byFlow[$flow][] = $id;
            }
        }

        ksort($byFlow);

        return $byFlow;
    }

    /**
     * ALPS の `link` とストーリーの食い違い。どれかが空でなければ食い違っている。各要素は `<rel> <href>` か `<rel>`。
     *
     * - missing: ストーリーが辿るのに、遷移に golden への `link` がない
     * - stale: 遷移の `link` の先に golden がない、またはそのストーリーがその rel を辿らない
     * - unknown: ストーリーが辿る rel が、ALPS の遷移にない
     *
     * @return array{missing: list<string>, stale: list<string>, unknown: list<string>}
     */
    public function drift(): array
    {
        $expected = $this->expectedLinks();
        $drift = ['missing' => [], 'stale' => [], 'unknown' => []];
        foreach ($expected as $rel => $hrefs) {
            if (! isset($this->transitions[$rel])) {
                $drift['unknown'][] = $rel;
                continue;
            }

            foreach (array_diff($hrefs, $this->transitions[$rel]['stories']) as $href) {
                $drift['missing'][] = sprintf('%s %s', $rel, $href);
            }
        }

        foreach ($this->transitions as $rel => $transition) {
            foreach ($transition['stories'] as $href) {
                if (! file_exists($this->root . '/' . $href) || ! in_array($href, $expected[$rel] ?? [], true)) {
                    $drift['stale'][] = sprintf('%s %s', $rel, $href);
                }
            }
        }

        return $drift;
    }

    public function transitionCount(): int
    {
        return count($this->transitions);
    }

    /** @return list<string> */
    private function relsOf(string $uri): array
    {
        $fragment = strstr($uri, '#link=');
        if ($fragment === false || strlen($fragment) === 0) {
            return [];
        }

        preg_match_all('/(?:#link=|,)[a-z]+:(\w+)/', $fragment, $matches);

        return array_values(array_unique($matches[1]));
    }
}
