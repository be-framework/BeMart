<?php

declare(strict_types=1);

use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;

require dirname(__DIR__) . '/vendor/autoload.php';

$target = dirname(__DIR__) . '/var/sql';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS),
);

$files = [];
foreach ($iterator as $file) {
    assert($file instanceof SplFileInfo);
    if ($file->isFile() && $file->getExtension() === 'sql') {
        $files[] = $file->getPathname();
    }
}

sort($files, SORT_STRING);

$formatter = new SqlFormatter(new NullHighlighter());
$formattedCount = 0;

foreach ($files as $file) {
    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "Failed to read SQL file: {$file}\n");

        exit(1);
    }

    $formatted = rtrim($formatter->format($sql)) . "\n";
    if ($formatted === $sql) {
        continue;
    }

    if (file_put_contents($file, $formatted) === false) {
        fwrite(STDERR, "Failed to write SQL file: {$file}\n");

        exit(1);
    }

    $formattedCount++;
}

printf("Formatted %d/%d SQL files.\n", $formattedCount, count($files));
