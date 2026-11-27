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

/**
 * ストーリーの行が宣言する `flow-*` と、ALPS の遷移のタグの突き合わせ。ALPS はテストを知らない。
 *
 * @psalm-import-type StoryRow from LinkStories
 */
final class StoryCoverage
{
    public const NO_FLOW = '(no flow tag)';
    public const FLOWS_MARKDOWN = 'docs/story-flows.md';
    public const STORIES = 'tests/Story/LinkStories.php';

    private const TRANSITION_TYPES = ['safe', 'unsafe', 'idempotent'];
    private const DIAGRAM_URL = 'alps.json.html?tag=%s&tagOnly=1&label=title';
    /** Where GitHub Pages publishes `docs/` */
    private const PUBLIC_DIAGRAM = 'https://be-framework.github.io/BeMart/';
    private const SEE = '// @see ';

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
     * 行が宣言した `flow-*` と、`#link=` が辿る遷移のタグの食い違い。宣言がなければ空。
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
     * - flow: 行が宣言した `flow-*` が、ALPS にない、または辿る遷移のタグと合わない（`<ストーリー名>: <理由>`）
     *
     * @return array{unknown: list<string>, flow: list<string>}
     */
    public function drift(): array
    {
        $drift = ['unknown' => [], 'flow' => []];
        foreach ($this->rows() as $name => $row) {
            foreach ($this->relsOf($row[0]) as $rel) {
                if (! isset($this->transitions[$rel])) {
                    $drift['unknown'][] = $rel;
                }
            }

            foreach ($this->flowMismatch($row[0], $this->flowsOf($row)) as $reason) {
                $drift['flow'][] = sprintf('%s: %s', $name, $reason);
            }
        }

        foreach (LinkStories::uploads() as $upload) {
            foreach ($this->relsOf($upload[0]) as $rel) {
                if (! isset($this->transitions[$rel])) {
                    $drift['unknown'][] = $rel;
                }
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
        foreach ($this->rows() as $name => $row) {
            foreach ($this->flowsOf($row) as $flow) {
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
     * 行は `'<ストーリー名>' => [` の形で書く。
     *
     * @throws LogicException 行がその形で 1 つだけ書かれていない
     */
    public function linkedStories(): string
    {
        $source = (string) preg_replace('/^[ \t]*' . preg_quote(self::SEE . self::PUBLIC_DIAGRAM, '/') . '.*\n/m', '', (string) file_get_contents($this->root . '/' . self::STORIES));
        foreach ($this->rows() as $name => $row) {
            $flows = $this->flowsOf($row);
            if ($flows === []) {
                continue;
            }

            $key = sprintf("'%s' => [", str_replace(['\\', "'"], ['\\\\', "\\'"], $name));
            $link = self::SEE . self::PUBLIC_DIAGRAM . sprintf(self::DIAGRAM_URL, implode(',', $flows));
            $source = (string) preg_replace_callback(
                '/^([ \t]*)' . preg_quote($key, '/') . '/m',
                static fn (array $match): string => $match[1] . $link . "\n" . $match[0],
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
     * golden を持つ行と、SQL で流す行。
     *
     * @return array<string, StoryRow>
     */
    private function rows(): array
    {
        return LinkStories::followable() + LinkStories::forMembers() + LinkStories::forBuyers() + LinkStories::persisted();
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
