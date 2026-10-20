#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Resolves which observation log to read, then renders it with stree.
 *
 * Usage: php tree.php [log.json] [stree options]   (default: the newest latest.json under var/log)
 *
 * `stree` takes a required file argument and knows nothing of this project's
 * `var/log/*\/observe/latest.json` convention, so this resolves the file first and echoes the
 * resolved path — `latest.json` changes on every request, and a stale tree must never pass
 * silently as evidence of the run that just happened.
 *
 * `body_ref` holds a `file://` URI into the body store (see ObserveModule); `stree` has no
 * domain knowledge of it and would render the full URI, truncated mid-path. This rewrites
 * `body_ref` to its basename on an in-memory copy of the log before handing off to `stree` —
 * the file on disk is untouched. Every other rendering decision (truncation, FQCN shortening,
 * the type that closes each scope) is `stree`'s; this file no longer reimplements any of it.
 *
 * Every argument not resolved as the log path is forwarded to `stree` verbatim — `--full`,
 * `--threshold`/`-t`, `--lines`/`-l`, `--values`/`-V`, `--json` all work exactly as documented
 * by `vendor/bin/stree --help`, including the space-separated value form (`--lines 20`, not
 * only `--lines=20`) since `-t`/`--threshold` and `-l`/`--lines` consume their following token
 * here the same way `StreeCommand::parseArgument()` does.
 */

function fail(string $message): never
{
    fwrite(STDERR, "FAIL  {$message}\n");
    exit(1);
}

is_file(getcwd() . '/vendor/autoload.php') || fail('no vendor/autoload.php in ' . getcwd() . ' — run from the app root');
require getcwd() . '/vendor/autoload.php';

use Koriym\SemanticLogger\Stree\StreeCommand;

$argvList = $argv;
array_shift($argvList);

// Flags that consume the next token as their value, mirroring StreeCommand's own parser — a
// bare "--lines 20" must forward both tokens to stree, not strand "20" as a second positional.
$valueFlags = ['--threshold', '-t', '--lines', '-l'];

$positional = [];
$streeArgs = [];
$count = count($argvList);
for ($i = 0; $i < $count; $i++) {
    $arg = $argvList[$i];

    if (in_array($arg, $valueFlags, true)) {
        $streeArgs[] = $arg;
        if ($i + 1 < $count) {
            $streeArgs[] = $argvList[++$i];
        }

        continue;
    }

    if ($arg !== '' && $arg[0] === '-') {
        $streeArgs[] = $arg;
        continue;
    }

    $positional[] = $arg;
}

$file = $positional[0] ?? null;
if ($file === null) {
    $found = glob(getcwd() . '/var/log/*/observe/latest.json') ?: [];
    $found !== [] || fail('no var/log/*/observe/latest.json — run a request first, or pass a path');
    usort($found, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));
    $file = $found[0];
}

is_file($file) || fail("no such file: {$file}");
$log = json_decode((string) file_get_contents($file), true);
is_array($log) || fail("not JSON: {$file}");

/**
 * Rewrites every `body_ref` string found anywhere in the tree to its basename, recursively —
 * the shape differs at each level (root `entries`/`close`, nested `children`/`close`), so this
 * walks blindly by key instead of encoding that shape a second time.
 *
 * @param array<array-key, mixed> $value
 *
 * @return array<array-key, mixed>
 */
function basenameBodyRef(array $value): array
{
    if (isset($value['body_ref']) && is_string($value['body_ref'])) {
        $value['body_ref'] = basename($value['body_ref']);
    }

    foreach ($value as $key => $item) {
        if (is_array($item)) {
            $value[$key] = basenameBodyRef($item);
        }
    }

    return $value;
}

$scratch = tempnam(sys_get_temp_dir(), 'tree_'); // StreeCommand reads by content, not extension.
$scratch !== false || fail('could not create a scratch file in ' . sys_get_temp_dir());
register_shutdown_function(static fn () => @unlink($scratch));
file_put_contents(
    $scratch,
    (string) json_encode(basenameBodyRef($log), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
);

echo $file, "\n";

exit((new StreeCommand())(['tree.php', $scratch, ...$streeArgs]));
