#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * CLI compile entry.
 *
 * Usage: php bin/compile.php [context]
 *
 * @see https://bearsunday.github.io/manuals/1.0/en/production.html#compilation-recommended
 */

use BEAR\Package\Compiler;

require dirname(__DIR__) . '/vendor/autoload.php';

ini_set('memory_limit', '-1');

$context = $argv[1] ?? 'cli-prod-eccube-sql-hal-app';

exit((new Compiler('MyVendor\BeMart', $context, dirname(__DIR__)))());
