<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use PHPUnit\Framework\TestCase;

class StoryCoverageTest extends TestCase
{
    public function testTheAlpsStoryLinksAndTheStoriesAgree(): void
    {
        $this->assertSame(
            ['missing' => [], 'stale' => [], 'unknown' => []],
            StoryCoverage::forRepository()->drift(),
            'Run `composer story:uncovered` and link the stories in alps.json (`rel: story`), or add the story.',
        );
    }
}
