#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Proves the observation wiring, binding by binding.
 *
 * Usage: php check.php [app-dir] [context]
 *
 * Every failure here is silent at runtime: the application answers correctly and the log
 * is simply thinner than it looks. Each line names the binding and what its failure hides.
 */

use BEAR\EventSourcing\Resource\SemanticLogInvoker;
use BEAR\Package\Injector;
use BEAR\QueryRepository\Log\LogSinkInterface;
use BEAR\RepositoryModule\Annotation\CacheLog;
use BEAR\RepositoryModule\Annotation\EtagPool;
use BEAR\RepositoryModule\Annotation\ResourceObjectPool;
use BEAR\Resource\InvokerInterface;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;

$argvList = $argv;
array_shift($argvList);
$appDir = realpath($argvList[0] ?? getcwd()) ?: '';
// A skeleton has a root autoload.php; an application assembled by hand may only have Composer's.
// Missing both is a fatal error before the first check runs, so it is reported as a FAIL like
// everything else here.
$autoload = is_file($appDir . '/autoload.php') ? '/autoload.php' : '/vendor/autoload.php';
if (! is_file($appDir . $autoload)) {
    fwrite(STDERR, "FAIL  no autoload.php or vendor/autoload.php in {$appDir}\n");
    exit(1);
}

require $appDir . $autoload;

$composer = json_decode((string) file_get_contents($appDir . '/composer.json'), true);
$namespace = '';
foreach (($composer['autoload']['psr-4'] ?? []) as $prefix => $dir) {
    if (in_array('src', array_map(static fn (string $d): string => rtrim($d, '/'), (array) $dir), true)) {
        $namespace = rtrim((string) $prefix, '\\');
        break;
    }
}

$context = $argvList[1] ?? 'cli-dev-hal-app';

try {
    $injector = Injector::getInstance($namespace, $context, $appDir);
} catch (Throwable $e) {
    fwrite(STDERR, "FAIL  the {$context} context does not build\n      " . $e::class . ': ' . $e->getMessage() . "\n");
    fwrite(STDERR, "      an Unbound here usually means DevModule is missing one of the observation bindings\n");
    exit(1);
}

$failures = 0;
$check = static function (string $name, bool $ok, string $hides) use (&$failures): void {
    echo $ok ? "PASS  {$name}\n" : "FAIL  {$name}\n      hides: {$hides}\n";
    $ok || $failures++;
};

// An Unbound is a failed check, not a crash: the missing binding is exactly what is being asked.
$get = static function (string $interface, string|null $qualifier = null) use ($injector): object|null {
    try {
        return $injector->getInstance($interface, $qualifier);
    } catch (Throwable) {
        return null;
    }
};

$logger = $get(SemanticLoggerInterface::class);
$check(
    'one logger for both keys',
    $logger !== null && $logger === $get(SemanticLoggerInterface::class, CacheLog::class),
    'the cache scopes go to a second logger that nothing flushes — resource_request stays, get/save_value vanish',
);

$check(
    'invoker is decorated',
    $get(InvokerInterface::class) instanceof SemanticLogInvoker,
    'no resource_request node at all — the tree has cache scopes with nothing around them',
);

// Both of these are bound on purpose elsewhere — NullAdapter by the default module, ArrayAdapter
// by test overrides — and both keep the pool inside one process, which is what a hit needs to
// outlive.
$processLocal = [NullAdapter::class, ArrayAdapter::class];
foreach ([ResourceObjectPool::class => 'resource object', EtagPool::class => 'etag'] as $qualifier => $label) {
    $pool = $get(AdapterInterface::class, $qualifier);
    $check(
        "{$label} pool survives the request",
        $pool !== null && ! in_array($pool::class, $processLocal, true),
        'every GET is a miss, so the log can never show a hit or a 304',
    );
}

$check(
    'a sink owns the flush',
    $get(LogSinkInterface::class) instanceof LogSinkInterface,
    'the log is built and then dropped at shutdown — no file is ever written',
);

echo $failures === 0 ? "\nok  run a request, then read var/log/{$context}/observe/latest.json\n" : "\n{$failures} failed\n";
exit($failures === 0 ? 0 : 1);
