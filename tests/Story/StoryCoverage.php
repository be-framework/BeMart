<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use LogicException;

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
use function file_get_contents;
use function file_put_contents;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function ksort;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function preg_replace_callback;
use function sort;
use function sprintf;
use function str_starts_with;
use function strlen;
use function strstr;
use function str_replace;

use const JSON_THROW_ON_ERROR;

/** ストーリーの行が宣言する `flow-*` と、ALPS の遷移のタグの突き合わせ。ALPS はテストを知らない。 */
final class StoryCoverage
{
    public const NO_FLOW = '(no flow tag)';
    public const FLOWS_MARKDOWN = 'docs/story-flows.md';
    public const STORIES = 'tests/Story/LinkStories.php';

    private const TRANSITION_TYPES = ['safe', 'unsafe', 'idempotent'];
    private const DIAGRAM_URL = 'alps.json.html?tag=%s&tagOnly=1&label=title';
    /** The comment above a row: GitHub Pages publishes `docs/` there */
    private const ROW_LINK = '// @see https://be-framework.github.io/BeMart/' . self::DIAGRAM_URL;

    /** @var array<string, list<string>> flow tags by transition id */
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
            $this->transitions[(string) $descriptor['id']] = array_values(array_filter($tags, static fn (string $tag): bool => str_starts_with($tag, 'flow-')));
        }
    }

    public static function forRepository(): self
    {
        return new self(dirname(__DIR__, 2));
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
        foreach ($this->followingStories() as [$uri, $flows]) {
            $rels = $this->relsOf($uri);
            $any = [...$any, ...$rels];
            foreach ($flows as $flow) {
                $declared[$flow] = [...($declared[$flow] ?? []), ...$rels];
            }
        }

        $coverage = [];
        foreach ($this->transitions as $id => $flows) {
            foreach ($flows ?: [self::NO_FLOW] as $flow) {
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
     * 行が宣言した `flow-*` と、`#link=` が辿る遷移のタグの食い違い。宣言のない行は、ALPS にない rel だけを見る。
     *
     * @param list<string> $flows
     *
     * @return list<string>
     */
    public function flowMismatch(string $uri, array $flows): array
    {
        $mismatch = [];
        foreach (array_diff($flows, $this->flowTags()) as $flow) {
            $mismatch[] = sprintf('%s is not a flow tag in alps.json', $flow);
        }

        $carried = [];
        foreach ($this->relsOf($uri) as $rel) {
            if (! isset($this->transitions[$rel])) {
                $mismatch[] = sprintf('%s is not a transition in alps.json', $rel);
                continue;
            }

            // A transition in no flow cannot contradict the row's flows
            if ($this->transitions[$rel] === []) {
                continue;
            }

            $tags = array_intersect($flows, $this->transitions[$rel]);
            if ($flows !== [] && $tags === []) {
                $mismatch[] = sprintf('%s has none of %s', $rel, implode(' ', $flows));
            }

            $carried = [...$carried, ...$tags];
        }

        foreach (array_diff($flows, $carried, array_diff($flows, $this->flowTags())) as $flow) {
            $mismatch[] = sprintf('%s is carried by none of the followed transitions', $flow);
        }

        return $mismatch;
    }

    /**
     * ストーリーと ALPS の食い違い。どれかが空でなければ食い違っている。
     *
     * - unknown: ストーリーが辿る rel が、ALPS の遷移にない（`<rel>`）
     * - flow: 行が宣言した `flow-*` が、ALPS にない、辿る遷移のタグと合わない、または辿る遷移に `flow-*` があるのに宣言しない（`<ストーリー名>: <理由>`）
     *
     * @return array{unknown: list<string>, flow: list<string>}
     */
    public function drift(): array
    {
        $drift = ['unknown' => [], 'flow' => []];
        foreach ($this->stories() as $name => [$uri, $flows]) {
            foreach ($this->relsOf($uri) as $rel) {
                if (! isset($this->transitions[$rel])) {
                    $drift['unknown'][] = $rel;
                } elseif ($flows === [] && $this->transitions[$rel] !== []) {
                    $drift['flow'][] = sprintf('%s: follows %s of %s but declares no flow', $name, $rel, implode(' ', $this->transitions[$rel]));
                }
            }

            foreach ($this->flowMismatch($uri, $flows) as $reason) {
                $drift['flow'][] = sprintf('%s: %s', $name, $reason);
            }
        }

        $drift['unknown'] = array_values(array_unique($drift['unknown']));

        return $drift;
    }

    /**
     * `flow-*` タグごとの、公開の ALPS 図の URL・そのフローを宣言したストーリーの行・そのフローを宣言したストーリーが辿らない遷移。
     * `docs/` に置くので、図へのリンクは相対にする。
     */
    public function flowsMarkdown(): string
    {
        $stories = [];
        foreach ($this->followingStories() as $name => [, $flows]) {
            foreach ($flows as $flow) {
                $stories[$flow][] = $name;
            }
        }

        $lines = [
            '# フローとストーリー',
            '',
            '`composer story:flows` が生成する。手で編集しない。',
            '',
            'ストーリーは `tests/Story/LinkStories.php` の行で、末尾の `flow-*` が、その行が辿る ALPS のフローを示す。',
            '',
            '| フロー | ALPS の図 | ストーリー | 宣言したストーリーが辿らない遷移 |',
            '|---|---|---|---|',
        ];
        foreach ($this->flowCoverage() as $flow => $transitions) {
            $diagram = $flow === self::NO_FLOW ? '-' : sprintf('[図](' . self::DIAGRAM_URL . ')', $flow);
            $lines[] = sprintf(
                '| `%s` | %s | %s | %s |',
                $flow,
                $diagram,
                $this->cell($stories[$flow] ?? []),
                $this->cell($transitions['unfollowed'], '`'),
            );
        }

        return implode("\n", $lines) . "\n";
    }

    public function isFlowsMarkdownCurrent(): bool
    {
        return file_get_contents($this->root . '/' . self::FLOWS_MARKDOWN) === $this->flowsMarkdown();
    }

    public function writeFlowsMarkdown(): void
    {
        file_put_contents($this->root . '/' . self::FLOWS_MARKDOWN, $this->flowsMarkdown());
    }

    /**
     * `LinkStories.php` に、`flow-*` を宣言した行ごとに、その行のフローで絞った公開の ALPS 図へのリンクを書いたもの。
     * 行は `'<ストーリー名>' => [` の形で 1 つだけ書く。
     *
     * @throws LogicException 行がその形で 1 つだけ書かれていない
     */
    public function linkedStories(): string
    {
        $stale = '/^[ \t]*' . preg_quote((string) strstr(self::ROW_LINK, '%s', true), '/') . '.*\n/m';
        $source = (string) preg_replace($stale, '', (string) file_get_contents($this->root . '/' . self::STORIES));
        foreach ($this->stories() as $name => [, $flows]) {
            $key = sprintf("'%s' => [", str_replace(['\\', "'"], ['\\\\', "\\'"], $name));
            $link = $flows === [] ? '' : sprintf(self::ROW_LINK, implode(',', $flows));
            $source = (string) preg_replace_callback(
                '/^([ \t]*)' . preg_quote($key, '/') . '/m',
                static fn (array $match): string => $link === '' ? $match[0] : $match[1] . $link . "\n" . $match[0],
                $source,
                -1,
                $count,
            );
            if ($count !== 1) {
                throw new LogicException(sprintf('Write the story "%s" as exactly one row %s in %s; found %d.', $name, $key, self::STORIES, $count));
            }
        }

        return $source;
    }

    public function isLinkedStoriesCurrent(): bool
    {
        return file_get_contents($this->root . '/' . self::STORIES) === $this->linkedStories();
    }

    public function writeLinkedStories(): void
    {
        file_put_contents($this->root . '/' . self::STORIES, $this->linkedStories());
    }

    public function transitionCount(): int
    {
        return count($this->transitions);
    }

    /**
     * 辿るストーリーに、`flow-*` を宣言しない行しかない遷移。
     *
     * @return list<string>
     */
    public function followedWithoutFlow(): array
    {
        $withFlow = [];
        $withoutFlow = [];
        foreach ($this->followingStories() as [$uri, $flows]) {
            if ($flows === []) {
                $withoutFlow = [...$withoutFlow, ...$this->relsOf($uri)];
            } else {
                $withFlow = [...$withFlow, ...$this->relsOf($uri)];
            }
        }

        $rels = array_values(array_diff(array_unique($withoutFlow), $withFlow));
        sort($rels);

        return $rels;
    }

    /** @return list<string> */
    private function flowTags(): array
    {
        $tags = [];
        foreach ($this->transitions as $flows) {
            $tags = [...$tags, ...$flows];
        }

        return array_values(array_unique($tags));
    }

    /** @param list<string> $items */
    private function cell(array $items, string $quote = ''): string
    {
        if ($items === []) {
            return '-';
        }

        return implode('<br>', array_map(static fn (string $item): string => $quote . str_replace('|', '\\|', $item) . $quote, $items));
    }

    /**
     * 辿るストーリー（golden を持つ行、SQL で流す行、ファイルを送る行）の URI と `flow-*`。
     *
     * @return array<string, array{string, list<string>}>
     */
    private function followingStories(): array
    {
        $stories = [];
        foreach (LinkStories::followable() + LinkStories::forAdminOrders() + LinkStories::forMembers() + LinkStories::forBuyers() + LinkStories::persisted() as $name => $row) {
            $stories[$name] = [$row[0], $this->flows($row[4] ?? [])];
        }

        foreach (LinkStories::uploads() as $name => $row) {
            $stories[$name] = [$row[0], $this->flows($row[5])];
        }

        return $stories;
    }

    /**
     * 辿るストーリーと、辿れないことを示すストーリーの URI と `flow-*`。
     *
     * @return array<string, array{string, list<string>}>
     */
    private function stories(): array
    {
        $stories = $this->followingStories();
        foreach (LinkStories::unfollowable() as $name => $row) {
            $stories[$name] = [$row[0], $this->flows($row[2])];
        }

        return $stories;
    }

    /**
     * @param string|list<string> $flow
     *
     * @return list<string>
     */
    private function flows(string|array $flow): array
    {
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
