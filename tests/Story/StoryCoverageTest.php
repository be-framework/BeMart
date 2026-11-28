<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function implode;
use function sprintf;

class StoryCoverageTest extends TestCase
{
    public function testTheFlowTagsOfTheStoriesAndAlpsAgree(): void
    {
        $this->assertSame(
            ['unknown' => [], 'flow' => [], 'golden' => []],
            StoryCoverage::forRepository()->drift(),
            'Fix the flow tag of the row, or the flow-* tags in alps.json; record a missing golden with UPDATE_GOLDEN=1.',
        );
    }

    public function testTheFlowTableIsCurrent(): void
    {
        $this->assertTrue(StoryCoverage::forRepository()->isFlowsMarkdownCurrent(), 'Run `composer story:flows`.');
    }

    public function testEachRowLinksToTheDiagramOfItsFlows(): void
    {
        $this->assertTrue(StoryCoverage::forRepository()->isLinkedStoriesCurrent(), 'Run `composer story:flows`.');
    }

    /**
     * @param list<string>   $links
     * @param list<int|null> $codes
     * @param list<string>   $followed
     */
    #[DataProvider('goldens')]
    public function testFollowedRelsAreTheLinksWhoseRequestWasMade(array $links, array $codes, array $followed): void
    {
        $this->assertSame($followed, StoryCoverage::forRepository()->followedRels(self::golden($links, $codes)));
    }

    /** @return iterable<string, array{list<string>, list<int|null>, list<string>}> */
    public static function goldens(): iterable
    {
        yield 'every link is followed' => [['self:a', 'self:b'], [200, 200, 200], ['a', 'b']];
        yield 'the chain stops early' => [['self:a', 'self:b', 'self:c'], [200, 200, 400], ['a', 'b']];
        yield 'a 303 is followed by the GET of its Location' => [['self:a', 'self:b'], [200, 303, 200, 200], ['a', 'b']];
        yield 'a 201 is followed by the GET of its Location' => [['self:a', 'self:b'], [200, 201, 200, 200], ['a', 'b']];
        yield 'a link after a 303 and its Location GET is still followed' => [['self:a', 'self:b', 'self:c'], [200, 303, 200, 400], ['a', 'b']];
        yield 'a link after a 201 and its Location GET is still followed' => [['self:a', 'self:b', 'self:c'], [200, 201, 200, 400], ['a', 'b']];
        yield 'a failed request without a code still follows its link' => [['self:a', 'self:b'], [200, null], ['a']];
        yield 'a story that follows nothing' => [[], [200], []];
    }

    /** A request inside another request belongs to no link */
    public function testARequestInsideARequestIsNotAStep(): void
    {
        $golden = self::golden(['self:a', 'self:b'], [200, 400], "│   ├── resource_request get 'page://self/embedded'\n│   │   └── close resource_response\n│   │       ├── code: 303");

        $this->assertSame(['a'], StoryCoverage::forRepository()->followedRels($golden));
    }

    /**
     * @param list<string>   $links
     * @param list<int|null> $codes
     */
    private static function golden(array $links, array $codes, string $nested = ''): string
    {
        $lines = ["link_request get 'page://self/start'", '├── uri: page://self/start'];
        foreach ($links as $i => $link) {
            $lines[] = sprintf('├── links[%d]: %s', $i, $link);
        }

        foreach ($codes as $code) {
            $lines[] = "├── resource_request get 'page://self/step'";
            $lines[] = '│   ├── method: GET';
            $nested === '' || $lines[] = $nested;
            if ($code !== null) {
                $lines[] = '│   └── close resource_response';
                $lines[] = sprintf('│       ├── code: %d', $code);
                $lines[] = '│       └── durationMs: [volatile]';
            }
        }

        $lines[] = '└── close link_response';

        return implode("\n", $lines) . "\n\nevents:\n(none)";
    }
}
