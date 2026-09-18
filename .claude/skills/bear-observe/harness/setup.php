#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Installs the observation context into a BEAR.Sunday application.
 *
 * Usage: php setup.php [app-dir] [entrypoint] [--force] [--context=<literal>]
 *
 * Writes src/Module/{DevModule,ObserveLoggerProvider,DevPoolProvider}.php and bin/dev.php.
 * The observation context is built from the context literal in the entrypoint (default
 * public/index.php), so an application with several entry points is observed one at a time.
 * Existing files are left alone unless --force. Nothing else in the application changes:
 * the flush is the cache log module's shutdown sink, not a line in public/index.php.
 */

$argvList = $argv;
array_shift($argvList);
$force = in_array('--force', $argvList, true);
$contextOption = null;
$notes = [];
foreach ($argvList as $arg) {
    if (str_starts_with($arg, '--context=')) {
        $contextOption = substr($arg, strlen('--context='));
    }
}
if ($contextOption !== null && preg_match('/^(?!-)[a-z0-9-]*app$/', $contextOption) !== 1) {
    fail("--context={$contextOption} does not match the '<word>-app' shape setup.php scrapes from entry points");
}
// Options are filtered before the positions are read; otherwise `php setup.php --force` names
// `--force` as the application directory.
$positional = array_values(array_filter($argvList, static fn (string $arg): bool => ! str_starts_with($arg, '--')));
$appDir = realpath($positional[0] ?? getcwd()) ?: '';
$templateDir = dirname(__DIR__) . '/templates';

function fail(string $message): never
{
    fwrite(STDERR, "FAIL  {$message}\n");
    exit(1);
}

$composerFile = $appDir . '/composer.json';
is_file($composerFile) || fail("no composer.json in {$appDir}");
$composer = json_decode((string) file_get_contents($composerFile), true);
is_array($composer) || fail("unreadable composer.json in {$appDir}");

$namespace = '';
foreach (($composer['autoload']['psr-4'] ?? []) as $prefix => $dir) {
    $dirs = (array) $dir;
    if (in_array('src', array_map(static fn (string $d): string => rtrim($d, '/'), $dirs), true)) {
        $namespace = rtrim((string) $prefix, '\\');
        break;
    }
}
$namespace !== '' || fail('no psr-4 prefix maps to src/ — is this a BEAR.Sunday application?');

$required = ['bear/event-sourcing', 'bear/query-repository'];
$missing = array_values(array_filter(
    $required,
    static fn (string $p): bool => ! isset($composer['require'][$p]) && ! isset($composer['require-dev'][$p]),
));
if ($missing !== []) {
    fail('missing dependencies: ' . implode(' ', $missing) . "\n      run: composer require --dev " . implode(':1.x-dev ', $missing) . ':1.x-dev');
}

// The observation context is the context of the entry point being observed with `cli-dev-` in
// front of it, so DevModule joins the chain that entry point actually runs rather than a second
// one.
$entryName = $positional[1] ?? 'public/index.php';
$entry = str_starts_with($entryName, '/') ? $entryName : $appDir . '/' . $entryName;
// A named entry point that is not there is a typo, and a typo writes nothing. A missing default
// only means there is nothing to read a context from.
if (! is_file($entry) && isset($positional[1])) {
    fail("no entry point at {$entry}");
}

// `cli` and `prod` are the SAPI and environment words BEAR.Package's context modules contribute;
// `dev` is the word this harness writes. The observation context supplies all three itself, so
// stripping them before comparing means `--context=html-app` matches a `prod-html-app` source
// literal the same way the scraped literal would, rather than only an exact source-text match.
$stripPrefixes = static fn (string $s): string => (string) preg_replace('/^(?:(?:cli|prod|dev)-)+/', '', $s);

// An entry point with two context literals (e.g. branching on the request path) cannot be
// disambiguated by regex; --context=<literal> names the one to observe directly. An entry
// that builds its context dynamically (env, const) may have no matching literal at all, so a
// mismatch is a note, not a fail — the escape hatch must still work for exactly those apps.
if ($contextOption !== null && is_file($entry)) {
    preg_match_all("/(['\"])([a-z0-9-]*app)\\1/", (string) file_get_contents($entry), $entryLiterals);
    if (! in_array($stripPrefixes($contextOption), array_map($stripPrefixes, $entryLiterals[2]), true)) {
        $notes[] = "--context={$contextOption} not found as a literal in {$entry} — verify this is the context you intended to observe";
    }
}
$literal = $contextOption
    ?? (is_file($entry) && preg_match("/(['\"])([a-z0-9-]*app)\\1/", (string) file_get_contents($entry), $m) === 1
        ? $m[2]
        : 'app');
$context = 'cli-dev-' . $stripPrefixes($literal);

$becomingWarning = '';
$written = [];
$kept = [];
$merge = [];
// The identifiers that must all appear for the file to be doing our job already. A DevModule that
// wraps the invoker but installs no log module leaves half a tree, so half the markers is a miss.
// This only decides whether to leave a template beside the file; whether observation works is
// check.php's answer, and it comes from the bindings.
$markers = [
    'DevModule' => ['SemanticLogInvoker', 'DevQueryRepositoryLogModule', 'SemanticLoggerInterface', 'ResourceObjectPool'],
    'ObserveLoggerProvider' => ['CacheLog'],
    'DevPoolProvider' => ['FilesystemAdapter'],
];
foreach ($markers as $class => $required) {
    $target = $appDir . '/src/Module/' . $class . '.php';
    $source = str_replace('__NAMESPACE__', $namespace, (string) file_get_contents($templateDir . '/' . $class . '.php'));

    if (is_file($target) && ! $force) {
        $existing = (string) file_get_contents($target);
        // An application's own module already carries bindings this cannot guess at, so the
        // template lands beside it and the merge is a reading job, not a rewrite.
        if (array_filter($required, static fn (string $m): bool => ! str_contains($existing, $m)) !== []) {
            file_put_contents($target . '.observe', $source);
            $merge[] = 'src/Module/' . $class . '.php';
            // Be Framework's BecomingInterface flushes the semantic logger after every becoming;
            // folding this template's logger binding into an app's own DevModule risks that
            // flush cutting a request's tree in two. Warned unconditionally, not only when this
            // file mentions BecomingInterface, because the shared logger is often wired through
            // a different class (e.g. override(new DevLoggingModule())) a same-file string
            // search would never see.
            if ($class === 'DevModule') {
                $becomingWarning = 'src/Module/DevModule.php already exists — confirm whether it '
                    . '(directly or through another module it installs/overrides) shares its '
                    . 'SemanticLoggerInterface with something that flushes mid-request (Be '
                    . 'Framework\'s BecomingInterface is the common case); if so, give the fold '
                    . 'its own context word instead of `dev` (see the "Wiring inside a '
                    . 'BEAR.Sunday context" section of the README)';
            }

            continue;
        }

        $kept[] = 'src/Module/' . $class . '.php';
        continue;
    }

    is_dir(dirname($target)) || mkdir(dirname($target), 0755, true);
    file_put_contents($target, $source);
    $written[] = 'src/Module/' . $class . '.php';
}

// Resolved here rather than in the generated file, so bin/dev.php keeps the single require line
// the application's own entry points have.
$autoload = is_file($appDir . '/autoload.php') ? '/autoload.php' : '/vendor/autoload.php';

$devBin = $appDir . '/bin/dev.php';
if (! is_file($devBin) || $force) {
    is_dir(dirname($devBin)) || mkdir(dirname($devBin), 0755, true);
    file_put_contents($devBin, <<<PHP
    <?php

    declare(strict_types=1);

    use {$namespace}\\Bootstrap;

    require dirname(__DIR__) . '{$autoload}';
    exit((new Bootstrap())('{$context}', \$GLOBALS, \$_SERVER));

    PHP);
    $written[] = 'bin/dev.php';
} else {
    $kept[] = 'bin/dev.php';
    // There is one bin/dev.php for however many entry points the application has. A kept one
    // that boots another context observes that other one, and the log below stays empty.
    if (! str_contains((string) file_get_contents($devBin), "'{$context}'")) {
        $notes[] = 'bin/dev.php runs another context; edit its context literal (--force also rewrites src/Module/*)';
    }
}
// bin/dev.php assumes {$namespace}\Bootstrap exists; a missing one is a silent runtime failure
// ("Class not found") the first time bin/dev.php runs, far from this message. Checked
// regardless of whether this run wrote or kept bin/dev.php: a kept file can outlive the
// Bootstrap class it referenced. A file at the expected path is not enough — it must actually
// declare the class in the expected namespace, or the note would wrongly stay silent.
$bootstrapFile = $appDir . '/src/Bootstrap.php';
$bootstrapSource = is_file($bootstrapFile) ? (string) file_get_contents($bootstrapFile) : '';
$hasBootstrapClass = preg_match('/\bnamespace\s+' . preg_quote($namespace, '/') . '\s*;/', $bootstrapSource) === 1
    && preg_match('/\bclass\s+Bootstrap\b/', $bootstrapSource) === 1;
if (! $hasBootstrapClass) {
    $notes[] = "{$namespace}\\Bootstrap not found at src/Bootstrap.php — bin/dev.php will fail until it exists";
}

echo "namespace  {$namespace}\n";
echo 'entry      ' . $entryName . (is_file($entry) ? '' : ' (absent — context defaults to app)') . "\n";
echo "context    {$context}\n";
echo "log        var/log/{$context}/observe/latest.json\n";
$written === [] || print('written    ' . implode(' ', $written) . "\n");
$kept === [] || print('kept       ' . implode(' ', $kept) . " (--force to overwrite)\n");
$notes === [] || print('note       ' . implode('; ', $notes) . "\n");
$merge === [] || print("merge      " . implode(' ', $merge) . " already exist — the observation bindings are in the sibling .observe file; fold them in by hand\n");
$becomingWarning === '' || print("warning    {$becomingWarning}\n");
echo 'next       php ' . dirname(__DIR__) . "/harness/check.php {$appDir} {$context}\n";
