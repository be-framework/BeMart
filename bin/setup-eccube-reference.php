<?php

declare(strict_types=1);

const ECCUBE_REFERENCE_REPOSITORY = 'https://github.com/EC-CUBE/ec-cube.git';
const ECCUBE_REFERENCE_BRANCH = '4.3';
const ECCUBE_REFERENCE_REVISION = '4706db5267016a05e933221cabb3144714425ffa';

$projectDir = dirname(__DIR__);
$targetDir = $projectDir . '/tools/ec-cube-source';

main($projectDir, $targetDir);

function main(string $projectDir, string $targetDir): void
{
    if (! is_dir($projectDir . '/tools')) {
        mkdir($projectDir . '/tools', 0777, true);
    }

    if (! is_dir($targetDir)) {
        run(sprintf(
            'git clone --depth 1 --branch %s %s %s',
            escapeshellarg(ECCUBE_REFERENCE_BRANCH),
            escapeshellarg(ECCUBE_REFERENCE_REPOSITORY),
            escapeshellarg($targetDir),
        ));
    }

    if (! is_dir($targetDir . '/.git')) {
        fwrite(STDERR, "EC-CUBE reference path exists but is not a git repository: {$targetDir}
");
        exit(1);
    }

    $head = git($targetDir, 'rev-parse HEAD');
    if ($head !== ECCUBE_REFERENCE_REVISION) {
        git($targetDir, sprintf('fetch --depth 1 origin %s', escapeshellarg(ECCUBE_REFERENCE_REVISION)));
        git($targetDir, sprintf('checkout --detach %s', escapeshellarg(ECCUBE_REFERENCE_REVISION)));
    }

    assertReferenceTemplatesExist($targetDir);

    fwrite(STDOUT, sprintf(
        "EC-CUBE reference ready: %s @ %s
",
        relativePath($projectDir, $targetDir),
        ECCUBE_REFERENCE_REVISION,
    ));
}

function assertReferenceTemplatesExist(string $targetDir): void
{
    foreach ([
        '/src/Eccube/Resource/template/default',
        '/src/Eccube/Resource/template/admin',
    ] as $path) {
        if (! is_dir($targetDir . $path)) {
            fwrite(STDERR, "EC-CUBE reference template directory missing: {$targetDir}{$path}
");
            exit(1);
        }
    }
}

function git(string $dir, string $args): string
{
    return run(sprintf('git -C %s %s', escapeshellarg($dir), $args));
}

function run(string $command): string
{
    $lines = [];
    $code = 0;
    exec($command . ' 2>&1', $lines, $code);
    $output = trim(implode("
", $lines));
    if ($code !== 0) {
        fwrite(STDERR, $output . "
");
        exit($code);
    }

    return $output;
}

function relativePath(string $baseDir, string $path): string
{
    $base = realpath($baseDir);
    $real = realpath($path);
    if ($base === false || $real === false || ! str_starts_with($real, $base . '/')) {
        return $path;
    }

    return implode('/', array_slice(explode('/', $real), count(explode('/', $base))));
}
