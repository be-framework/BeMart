#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Renders an observation log as a tree, naming the type that closes each scope.
 *
 * Usage: php tree.php [log.json] [--full]   (default: the newest latest.json under var/log)
 *
 * `stree` prints the closing context but not its type, and here the type carries the answer:
 * `get` closed by `cache_hit` and `get` closed by `cache_miss` have the same context keys.
 *
 * `--full` disables the 60-char value truncation; without it, a cut value is marked
 * `...(+N)` with the number of characters dropped, so it reads as cut rather than as a
 * value that happened to end there.
 */

$argvList = $argv;
array_shift($argvList);

$full = false;
$positional = [];
foreach ((is_array($argvList) ? $argvList : []) as $arg) {
    if ($arg === '--full') {
        $full = true;
        continue;
    }

    $positional[] = $arg;
}

function fail(string $message): never
{
    fwrite(STDERR, "FAIL  {$message}\n");
    exit(1);
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

// Runs in the application's PHP, which may be a no-dev install without ext-mbstring.
function truncate(string $text, bool $full): string
{
    if ($full) {
        return $text;
    }

    if (function_exists('mb_substr')) {
        $length = mb_strlen($text);

        return $length > 60 ? mb_substr($text, 0, 57) . '...(+' . ($length - 57) . ')' : $text;
    }

    $length = strlen($text);

    return $length > 60 ? substr($text, 0, 57) . '...(+' . ($length - 57) . ')' : $text;
}

// A value whose whole string is backslash-joined identifiers is a FQCN, not a URI, a JSON
// blob, or free text — none of those parse as this pattern.
function shortenFqcn(string $text): string
{
    if (! str_contains($text, '\\') || preg_match('/^(\\\\?[A-Za-z_][A-Za-z0-9_]*)+$/', $text) !== 1) {
        return $text;
    }

    $pos = strrpos($text, '\\');

    return $pos === false ? $text : substr($text, $pos + 1);
}

function formatScalar(bool|int|float|string $value): string
{
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    return (string) $value;
}

/** @param array<string, mixed> $context */
function summarize(array $context, bool $full): string
{
    $parts = [];
    foreach ($context as $key => $value) {
        if ($key === 'timestamp' || $value === null || $value === []) {
            continue;
        }

        $text = is_scalar($value) ? formatScalar($value) : (string) json_encode($value, JSON_UNESCAPED_SLASHES);
        if ($key === 'body_ref') {
            $text = basename($text);
        } elseif (is_string($value)) {
            $text = shortenFqcn($text);
        }

        $parts[] = $key . '=' . truncate($text, $full);
    }

    return implode(' ', $parts);
}

/** @param array<string, mixed> $node */
function render(array $node, bool $full, string $indent = ''): void
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
            ? ' → ' . $close['type'] . ' ' . summarize((array) ($close['context'] ?? []), $full)
            : '';
        echo $indent, $branch, $child['type'], ' ', summarize((array) ($child['context'] ?? []), $full), $arrow, "\n";
        render($child, $full, $next);
    }
}

echo $file, "\n";
render($log, $full);
