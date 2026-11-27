#!/usr/bin/env php
<?php

declare(strict_types=1);

use MyVendor\BeMart\Tests\Story\StoryCoverage;

require dirname(__DIR__) . '/vendor/autoload.php';

$coverage = StoryCoverage::forRepository();

if (in_array('--write', $argv, true)) {
    $coverage->writeLinkedStories();
    $coverage->writeFlowsMarkdown();
    echo StoryCoverage::FLOWS_MARKDOWN . ' and the diagram links in ' . StoryCoverage::STORIES . " written.\n";
    exit(0);
}

if (! in_array('--check', $argv, true)) {
    echo "Transitions by flow tag, and whether a story declaring that flow follows them:\n";
    foreach ($coverage->flowCoverage() as $flow => $transitions) {
        $followed = count($transitions['followed']);
        echo sprintf("  %s: %d of %d followed. Not followed: %s\n", $flow, $followed, $followed + count($transitions['unfollowed']), implode(' ', $transitions['unfollowed']) ?: '-');
    }

    $withoutFlow = $coverage->followedWithoutFlow();
    echo sprintf("Followed only by a story that declares no flow (%d): %s\n", count($withoutFlow), implode(' ', $withoutFlow));
}

$messages = [
    'unknown' => 'a story follows it but it is not a transition in alps.json',
    'flow' => 'the flow tag a story declares does not match alps.json',
];
$failed = false;
foreach (array_filter($coverage->drift()) as $kind => $items) {
    fwrite(STDERR, sprintf("%s: %s\n", $messages[$kind], implode('; ', $items)));
    $failed = true;
}

if (! $coverage->isFlowsMarkdownCurrent()) {
    fwrite(STDERR, StoryCoverage::FLOWS_MARKDOWN . " is stale. Run `composer story:flows`.\n");
    $failed = true;
}

if (! $coverage->isLinkedStoriesCurrent()) {
    fwrite(STDERR, 'The diagram links in ' . StoryCoverage::STORIES . " are missing or stale. Run `composer story:flows`.\n");
    $failed = true;
}

exit($failed ? 1 : 0);
