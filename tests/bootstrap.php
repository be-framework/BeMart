<?php

declare(strict_types=1);

/**
 * Compile the contexts the suite shares, once, before anything runs.
 *
 * A non-production context compiles its object graph lazily: `PackageInjector::factory()` hands
 * back a `RayInjector` that writes each generated class into `var/tmp/<context>/di` as it is first
 * asked for. That is fine for one process with one graph, and it is not what this suite is - the
 * HTTP tests run a server on the same context an in-process workflow test is already driving, and
 * a generated class file that disappears between generation and AOP weaving surfaces as
 * `Ray\Aop\Exception\InvalidSourceClassException` on a name that looks unrelated to anything.
 *
 * Compiling here removes the window: every class exists before the first test, so nothing is
 * generated while the suite is running. `warmup()` then instantiates the singletons the compiled
 * graph declares, so lazy singleton initialisation cannot race either. It costs ~0.3s per context.
 *
 * The script dir is rebuilt rather than reused: a stale dir from a run whose bindings differed is
 * the other half of the same problem, and freshness here is what lets the tests stop clearing it
 * underneath each other.
 */

use BEAR\AppMeta\Meta;
use BEAR\Package\Module;
use BEAR\Package\Module\ResourceObjectModule;
use Ray\Compiler\CompiledInjector;
use Ray\Compiler\Compiler;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * The contexts more than one process touches. `test` / `test-admin` are driven in-process only,
 * by a plain Ray.Di injector, so nothing shares their script dir.
 */
const SHARED_TEST_CONTEXTS = [
    'html-eccube-sql-hal-app',
    'html-test-hal-app',
    'prod-eccube-sql-hal-app',
];

$appDir = dirname(__DIR__);

foreach (SHARED_TEST_CONTEXTS as $context) {
    $meta = new Meta('MyVendor\\BeMart', $context, $appDir);
    $scriptDir = $meta->tmpDir . '/di';

    // rm -rf, without the shell: a partially written dir is worse than none.
    if (is_dir($scriptDir)) {
        foreach ((array) glob($scriptDir . '/*') as $file) {
            is_string($file) && is_file($file) && unlink($file);
        }
    } else {
        mkdir($scriptDir, 0777, true);
    }

    // The same graph PackageInjector::factory() builds - resource objects included, or a compiled
    // context cannot create the very resources the tests ask for.
    $module = (new Module())($meta, $context);
    $module->install(new ResourceObjectModule($meta->getResourceListGenerator()));

    (new Compiler())->compile($module, $scriptDir);
    (new CompiledInjector($scriptDir))->warmup();
}
