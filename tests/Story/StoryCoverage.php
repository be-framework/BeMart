<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use function array_diff;
use function array_filter;
use function array_intersect;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function assert;
use function count;
use function dirname;
use function explode;
use function file_exists;
use function file_get_contents;
use function implode;
use function in_array;
use function is_array;
use function is_string;
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
 * ALPS の遷移と、ストーリーが辿る rel の突き合わせ。
 *
 * - `followable()` の行は golden を持つので、辿る遷移の `link` に golden への href（rel=story）を 1 行ずつ足す。
 * - 行が宣言する `flow-*` は、辿る遷移のタグと突き合わせる。
 *
 * @psalm-import-type StoryRow from LinkStories
 */
final class StoryCoverage
{
    public const REL = 'story';
    public const NO_FLOW = '(no flow tag)';

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
     * `flow-*` タグごとに、そのタグの遷移を、そのフローを宣言したストーリーが辿るかどうか。
     * フロータグのない遷移は `(no flow tag)` にまとめ、どれかのストーリーが辿るかで分ける。
     *
     * @return array<string, array{followed: list<string>, unfollowed: list<string>}>
     */
    public function flowCoverage(): array
    {
        $any = [];
        $declared = [];
        foreach ($this->rows() as $row) {
            $rels = $this->relsOf($row[0]);
            $any = [...$any, ...$rels];
            foreach ($this->flowsOf($row) as $flow) {
                $declared[$flow] = [...($declared[$flow] ?? []), ...$rels];
            }
        }

        foreach (LinkStories::uploads() as $upload) {
            $any = [...$any, ...$this->relsOf($upload[0])];
        }

        $coverage = [];
        foreach ($this->transitions as $id => $transition) {
            foreach ($transition['flows'] ?: [self::NO_FLOW] as $flow) {
                $followers = $flow === self::NO_FLOW ? $any : ($declared[$flow] ?? []);
                $coverage[$flow][in_array($id, $followers, true) ? 'followed' : 'unfollowed'][] = $id;
            }
        }

        ksort($coverage);

        return array_map(
            static fn (array $ids): array => ['followed' => $ids['followed'] ?? [], 'unfollowed' => $ids['unfollowed'] ?? []],
            $coverage,
        );
    }

    /**
     * 行が宣言した `flow-*` と、`#link=` が辿る遷移のタグの食い違い。宣言がなければ空。
     *
     * @param list<string> $flows
     *
     * @return list<string>
     */
    public function flowMismatch(string $uri, array $flows): array
    {
        $mismatch = [];
        $carried = [];
        foreach ($this->relsOf($uri) as $rel) {
            if (! isset($this->transitions[$rel])) {
                $mismatch[] = sprintf('%s is not a transition in alps.json', $rel);
                continue;
            }

            $tags = array_intersect($flows, $this->transitions[$rel]['flows']);
            if ($flows !== [] && $tags === []) {
                $mismatch[] = sprintf('%s has none of %s', $rel, implode(' ', $flows));
            }

            $carried = [...$carried, ...$tags];
        }

        foreach (array_diff($flows, $carried) as $flow) {
            $mismatch[] = sprintf('%s is carried by none of the followed transitions', $flow);
        }

        return $mismatch;
    }

    /**
     * ALPS とストーリーの食い違い。どれかが空でなければ食い違っている。
     *
     * - missing: ストーリーが辿るのに、遷移に golden への `link`（`<rel> <href>`）がない
     * - stale: 遷移の `link` の先に golden がない、またはそのストーリーがその rel を辿らない（`<rel> <href>`）
     * - unknown: ストーリーが辿る rel が、ALPS の遷移にない（`<rel>`）
     * - flow: 行が宣言した `flow-*` が、辿る遷移のタグと合わない（`<ストーリー名>: <理由>`）
     *
     * @return array{missing: list<string>, stale: list<string>, unknown: list<string>, flow: list<string>}
     */
    public function drift(): array
    {
        $expected = $this->expectedLinks();
        $drift = ['missing' => [], 'stale' => [], 'unknown' => [], 'flow' => []];
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

        foreach ($this->rows() as $name => $row) {
            foreach ($this->flowMismatch($row[0], $this->flowsOf($row)) as $reason) {
                $drift['flow'][] = sprintf('%s: %s', $name, $reason);
            }
        }

        return $drift;
    }

    public function transitionCount(): int
    {
        return count($this->transitions);
    }

    /**
     * 行が辿るのに宣言する `flow-*` を持たない行がある遷移（`uploads()` を含む）。
     *
     * @return list<string>
     */
    public function followedWithoutFlow(): array
    {
        $withFlow = [];
        $withoutFlow = [];
        foreach ($this->rows() as $row) {
            $rels = $this->relsOf($row[0]);
            if ($this->flowsOf($row) === []) {
                $withoutFlow = [...$withoutFlow, ...$rels];
            } else {
                $withFlow = [...$withFlow, ...$rels];
            }
        }

        foreach (LinkStories::uploads() as $upload) {
            $withoutFlow = [...$withoutFlow, ...$this->relsOf($upload[0])];
        }

        $rels = array_values(array_diff(array_unique($withoutFlow), $withFlow));
        sort($rels);

        return $rels;
    }

    /**
     * golden を持つ行と、SQL で流す行。
     *
     * @return array<string, StoryRow>
     */
    private function rows(): array
    {
        return LinkStories::followable() + LinkStories::persisted();
    }

    /**
     * @param StoryRow $row
     *
     * @return list<string>
     */
    private function flowsOf(array $row): array
    {
        $flow = $row[4] ?? [];

        return is_string($flow) ? [$flow] : $flow;
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
