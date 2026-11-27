<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use PHPUnit\Framework\TestCase;

class StoryCoverageTest extends TestCase
{
    public function testTheFlowTagsOfTheStoriesAndAlpsAgree(): void
    {
        $this->assertSame(
            ['unknown' => [], 'flow' => []],
            StoryCoverage::forRepository()->drift(),
            'Fix the flow tag of the row, or the flow-* tags in alps.json.',
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
}
