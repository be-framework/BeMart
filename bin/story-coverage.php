#!/usr/bin/env php
<?php

declare(strict_types=1);

use MyVendor\BeMart\Tests\Story\StoryCoverage;

require dirname(__DIR__) . '/vendor/autoload.php';

$coverage = StoryCoverage::forRepository();
$drift = array_filter($coverage->drift());
$checkOnly = in_array('--check', $argv, true);

if (! $checkOnly) {
    $uncovered = $coverage->uncovered();
    echo "Transitions with no story, by flow:\n";
    foreach ($uncovered as $flow => $rels) {
        echo sprintf("  %s (%d): %s\n", $flow, count($rels), implode(' ', $rels));
    }

    echo sprintf("%d of %d transitions are followed by a story with a golden.\n", count($coverage->expectedLinks()), $coverage->transitionCount());
    $withoutGolden = $coverage->followedWithoutGolden();
    echo sprintf("Followed only by a story without a golden (uploads, persisted), so not linked from alps.json (%d): %s\n", count($withoutGolden), implode(' ', $withoutGolden));
}

if ($drift === []) {
    exit(0);
}

$messages = [
    'missing' => 'a story follows it but the transition has no ' . StoryCoverage::REL . ' link to its golden in alps.json',
    'stale' => 'the ' . StoryCoverage::REL . ' link in alps.json points at a missing golden or a story that does not follow the transition',
    'unknown' => 'a story follows it but it is not a transition in alps.json',
];
foreach ($drift as $kind => $rels) {
    fwrite(STDERR, sprintf("%s: %s\n", $messages[$kind], implode(' ', $rels)));
}

exit(1);
