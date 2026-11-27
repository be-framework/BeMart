#!/usr/bin/env php
<?php

declare(strict_types=1);

use MyVendor\BeMart\Tests\Story\StoryCoverage;

require dirname(__DIR__) . '/vendor/autoload.php';

$coverage = StoryCoverage::forRepository();
$drift = array_filter($coverage->drift());
$checkOnly = in_array('--check', $argv, true);

if (! $checkOnly) {
    echo "Transitions by flow tag, and whether a story declaring that flow follows them:\n";
    foreach ($coverage->flowCoverage() as $flow => $transitions) {
        $followed = count($transitions['followed']);
        echo sprintf("  %s: %d of %d followed. Not followed: %s\n", $flow, $followed, $followed + count($transitions['unfollowed']), implode(' ', $transitions['unfollowed']) ?: '-');
    }

    $withoutGolden = $coverage->followedWithoutGolden();
    echo sprintf("Followed only by a story without a golden (uploads, persisted), so not linked from alps.json (%d): %s\n", count($withoutGolden), implode(' ', $withoutGolden));
    $withoutFlow = $coverage->followedWithoutFlow();
    echo sprintf("Followed only by a story that declares no flow (%d): %s\n", count($withoutFlow), implode(' ', $withoutFlow));
}

if ($drift === []) {
    exit(0);
}

$messages = [
    'missing' => 'a story follows it but the transition has no ' . StoryCoverage::REL . ' link to its golden in alps.json',
    'stale' => 'the ' . StoryCoverage::REL . ' link in alps.json points at a missing golden or a story that does not follow the transition',
    'unknown' => 'a story follows it but it is not a transition in alps.json',
    'flow' => 'the flow tag a story declares does not match the transitions it follows in alps.json',
];
foreach ($drift as $kind => $rels) {
    fwrite(STDERR, sprintf("%s: %s\n", $messages[$kind], implode(' ', $rels)));
}

exit(1);
