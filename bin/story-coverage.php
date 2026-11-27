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

    echo sprintf("%d of %d transitions are followed by a story.\n", count($coverage->followedRels()), $coverage->transitionCount());
}

if ($drift === []) {
    exit(0);
}

$messages = [
    'untagged' => 'followed by a story but not tagged ' . StoryCoverage::TAG . ' in alps.json',
    'stale' => 'tagged ' . StoryCoverage::TAG . ' in alps.json but no story follows it',
    'unknown' => 'followed by a story but not a transition in alps.json',
];
foreach ($drift as $kind => $rels) {
    fwrite(STDERR, sprintf("%s: %s\n", $messages[$kind], implode(' ', $rels)));
}

exit(1);
