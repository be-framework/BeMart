#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * CLI compile entry.
 *
 * Usage: php bin/compile.php [context]
 *        APP_WRITE_DIR=/absolute/path php bin/compile.php [context]
 *
 * @see https://bearsunday.github.io/manuals/1.0/en/production.html#compilation-recommended
 */

use BEAR\Package\Compiler;

require dirname(__DIR__) . '/vendor/autoload.php';

ini_set('memory_limit', '-1');

// Build-time-only stubs (null objects / fake env) for services this machine cannot reach.
$dotCompile = dirname(__DIR__) . '/.compile.php';
is_file($dotCompile) && require $dotCompile;

$context = $argv[1] ?? 'cli-prod-eccube-sql-hal-app';
$writeDir = getenv('APP_WRITE_DIR') ?: null;

exit((new Compiler('MyVendor\BeMart', $context, dirname(__DIR__), $writeDir))());
