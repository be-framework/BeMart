<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use PHPUnit\Framework\TestCase;

class StoryCoverageTest extends TestCase
{
    public function testTheAlpsTagAndTheStoriesAgree(): void
    {
        $this->assertSame(
            ['untagged' => [], 'stale' => [], 'unknown' => []],
            StoryCoverage::forRepository()->drift(),
            'Run `composer story:uncovered` and tag the transitions in alps.json, or add the story.',
        );
    }
}
