<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use function array_diff;
use function array_filter;
use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function assert;
use function count;
use function dirname;
use function explode;
use function file_get_contents;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function ksort;
use function preg_match;
use function preg_match_all;
use function sort;
use function str_starts_with;
use function strstr;

use const JSON_THROW_ON_ERROR;

/**
 * ALPS の遷移と、ストーリーが辿る rel の突き合わせ。ストーリーが辿る遷移には ALPS 側に `story-covered` タグを付ける。
 */
final class StoryCoverage
{
    public const TAG = 'story-covered';

    private const TRANSITION_TYPES = ['safe', 'unsafe', 'idempotent'];

    /** @var array<string, array{tags: list<string>, flows: list<string>}> */
    private array $transitions;

    public function __construct(string $alpsFile)
    {
        $alps = json_decode((string) file_get_contents($alpsFile), true, 512, JSON_THROW_ON_ERROR);
        assert(is_array($alps) && is_array($alps['alps']['descriptor'] ?? null));
        $this->transitions = [];
        foreach ($alps['alps']['descriptor'] as $descriptor) {
            if (! in_array($descriptor['type'] ?? null, self::TRANSITION_TYPES, true)) {
                continue;
            }

            $tags = explode(' ', (string) ($descriptor['tag'] ?? ''));
            $this->transitions[(string) $descriptor['id']] = [
                'tags' => $tags,
                'flows' => array_values(array_filter($tags, static fn (string $tag): bool => str_starts_with($tag, 'flow-'))),
            ];
        }
    }

    public static function forRepository(): self
    {
        return new self(dirname(__DIR__, 2) . '/alps.json');
    }

    /**
     * 辿るストーリー（followable・uploads・persisted）の `#link=` が通る rel。`unfollowable` の行は辿れない例なので数えない。
     *
     * @return list<string>
     */
    public function followedRels(): array
    {
        $rels = [];
        foreach ([LinkStories::followable(), LinkStories::uploads(), LinkStories::persisted()] as $stories) {
            foreach ($stories as $story) {
                $fragment = strstr($story[0], '#link=');
                if ($fragment === false) {
                    continue;
                }

                preg_match_all('/(?:#link=|,)[a-z]+:(\w+)/', $fragment, $matches);
                $rels = array_merge($rels, $matches[1]);
            }
        }

        $rels = array_values(array_unique($rels));
        sort($rels);

        return $rels;
    }

    /** @return list<string> */
    public function taggedRels(): array
    {
        $tagged = array_keys(array_filter($this->transitions, static fn (array $t): bool => in_array(self::TAG, $t['tags'], true)));
        sort($tagged);

        return $tagged;
    }

    /**
     * ストーリーが辿っていない遷移を flow ごとにまとめる。flow タグがないものは `(no flow)`。
     *
     * @return array<string, list<string>>
     */
    public function uncovered(): array
    {
        $followed = $this->followedRels();
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
     * ALPS のタグとストーリーの食い違い。どちらかが空でなければ食い違っている。
     *
     * @return array{untagged: list<string>, stale: list<string>, unknown: list<string>}
     */
    public function drift(): array
    {
        $followed = $this->followedRels();
        $tagged = $this->taggedRels();
        $known = array_keys($this->transitions);

        return [
            'untagged' => array_values(array_diff(array_filter($followed, static fn (string $rel): bool => in_array($rel, $known, true)), $tagged)),
            'stale' => array_values(array_diff($tagged, $followed)),
            'unknown' => array_values(array_diff($followed, $known)),
        ];
    }

    public function transitionCount(): int
    {
        return count($this->transitions);
    }
}
