#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Renders an observation log as a tree, naming the type that closes each scope.
 *
 * Usage: php tree.php [log.json]   (default: the newest latest.json under var/log)
 *
 * `stree` prints the closing context but not its type, and here the type carries the answer:
 * `get` closed by `cache_hit` and `get` closed by `cache_miss` have the same context keys.
 */

$argvList = $argv;
array_shift($argvList);

function fail(string $message): never
{
    fwrite(STDERR, "FAIL  {$message}\n");
    exit(1);
}

$file = $argvList[0] ?? null;
if ($file === null) {
    $found = glob(getcwd() . '/var/log/*/observe/latest.json') ?: [];
    $found !== [] || fail('no var/log/*/observe/latest.json — run a request first, or pass a path');
    usort($found, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));
    $file = $found[0];
}

is_file($file) || fail("no such file: {$file}");
$log = json_decode((string) file_get_contents($file), true);
is_array($log) || fail("not JSON: {$file}");

// Runs in the application's PHP, which may be a no-dev install without ext-mbstring.
function truncate(string $text): string
{
    if (function_exists('mb_substr')) {
        return mb_strlen($text) > 60 ? mb_substr($text, 0, 57) . '...' : $text;
    }

    return strlen($text) > 60 ? substr($text, 0, 57) . '...' : $text;
}

/** @param array<string, mixed> $context */
function summarize(array $context): string
{
    $parts = [];
    foreach ($context as $key => $value) {
        if ($key === 'timestamp' || $value === null || $value === []) {
            continue;
        }

        $text = is_scalar($value) ? (string) $value : (string) json_encode($value, JSON_UNESCAPED_SLASHES);
        if ($key === 'body_ref') {
            $text = basename($text);
        }

        $parts[] = $key . '=' . truncate($text);
    }

    return implode(' ', $parts);
}

/** @param array<string, mixed> $node */
function render(array $node, string $indent = ''): void
{
    // Events are recorded against the enclosing scope, so they belong under it, ahead of the
    // scopes it went on to open.
    // The file is written by another process and may be any semantic log, so a child that carries
    // no type is skipped rather than rendered as a blank branch.
    $children = array_values(array_filter(
        array_merge((array) ($node['events'] ?? []), (array) ($node['open'] ?? [])),
        static fn (mixed $child): bool => is_array($child) && is_string($child['type'] ?? null),
    ));
    $last = count($children) - 1;
    foreach ($children as $i => $child) {
        $branch = $i === $last ? '└── ' : '├── ';
        $next = $indent . ($i === $last ? '    ' : '│   ');
        $close = $child['close'] ?? null;
        $arrow = is_array($close) && is_string($close['type'] ?? null)
            ? ' → ' . $close['type'] . ' ' . summarize((array) ($close['context'] ?? []))
            : '';
        echo $indent, $branch, $child['type'], ' ', summarize((array) ($child['context'] ?? [])), $arrow, "\n";
        render($child, $next);
    }
}

echo $file, "\n";
render($log);
