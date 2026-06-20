<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use PHPUnit\Framework\TestCase;

use function array_keys;
use function file_get_contents;
use function glob;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function preg_match_all;
use function sort;
use function sprintf;
use function str_starts_with;

use const JSON_THROW_ON_ERROR;

/**
 * Guard the deliberately-skipped operations registry.
 *
 * docs/eccube-spec-coverage/skipped-operations.json is the single record of
 * operations we knowingly do NOT exercise in any workflow test. This test keeps
 * the registry honest in both directions:
 *
 *  (a) every op listed in the registry is genuinely NOT exercised by any
 *      #[Alps('op')] in tests/Hypermedia/Flow*.php or tests/Html/Flow*.php
 *      (a newly-covered op must be REMOVED from the registry);
 *  (b) every alps.json descriptor of type unsafe|idempotent whose id starts with
 *      'do' is EITHER in the registry OR exercised by some flow test
 *      (a new uncovered op must be ADDED to the registry or covered).
 */
final class WorkflowSkipRegistryTest extends TestCase
{
    private const REGISTRY_PATH = __DIR__ . '/../../docs/eccube-spec-coverage/skipped-operations.json';
    private const ALPS_PATH = __DIR__ . '/../../alps.json';

    private const VALID_CATEGORIES = [
        'out-of-scope',
        'js-only',
        'intentionally-forbidden',
        'not-implemented-backlog',
    ];

    /** @return array<string, array{category: string, reason: string, evidence: list<string>}> */
    private function registry(): array
    {
        $json = file_get_contents(self::REGISTRY_PATH);
        self::assertIsString($json, 'skip registry must be readable: ' . self::REGISTRY_PATH);
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('skipped', $data);
        self::assertIsArray($data['skipped']);

        return $data['skipped'];
    }

    /**
     * Every op id exercised by a flow test via #[Alps('op')].
     *
     * @return list<string>
     */
    private function coveredOps(): array
    {
        $covered = [];
        $files = glob(__DIR__ . '/Flow*.php');
        $htmlFiles = glob(__DIR__ . '/../Html/Flow*.php');
        self::assertIsArray($files);
        self::assertIsArray($htmlFiles);

        foreach ([...$files, ...$htmlFiles] as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);
            preg_match_all("/#\\[Alps\\('(?P<op>[^']+)'\\)\\]/", $source, $matches);
            foreach ($matches['op'] as $op) {
                $covered[$op] = true;
            }
        }

        return array_keys($covered);
    }

    /**
     * Every alps.json descriptor of type unsafe|idempotent whose id starts with 'do'.
     *
     * @return list<string>
     */
    private function doOps(): array
    {
        $json = file_get_contents(self::ALPS_PATH);
        self::assertIsString($json);
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        $descriptors = $data['alps']['descriptor'];
        self::assertIsArray($descriptors);

        $ops = [];
        foreach ($descriptors as $descriptor) {
            $id = $descriptor['id'] ?? '';
            $type = $descriptor['type'] ?? '';
            if (is_string($id) && str_starts_with($id, 'do') && in_array($type, ['unsafe', 'idempotent'], true)) {
                $ops[] = $id;
            }
        }

        sort($ops);

        return $ops;
    }

    public function testRegistryEntriesAreWellFormed(): void
    {
        $registry = $this->registry();
        self::assertNotEmpty($registry, 'skip registry must list at least one operation.');

        foreach ($registry as $op => $entry) {
            self::assertIsString($op);
            self::assertIsArray($entry, sprintf('%s entry must be an object.', $op));

            self::assertArrayHasKey('category', $entry, sprintf('%s must declare a category.', $op));
            self::assertContains(
                $entry['category'],
                self::VALID_CATEGORIES,
                sprintf('%s has unknown category "%s".', $op, (string) $entry['category']),
            );

            self::assertArrayHasKey('reason', $entry, sprintf('%s must declare a reason.', $op));
            self::assertIsString($entry['reason']);
            self::assertNotSame('', $entry['reason'], sprintf('%s reason must not be empty.', $op));

            self::assertArrayHasKey('evidence', $entry, sprintf('%s must declare evidence.', $op));
            self::assertIsArray($entry['evidence']);
            self::assertNotEmpty($entry['evidence'], sprintf('%s must cite at least one evidence item.', $op));
        }
    }

    public function testSkippedOpsAreNotExercisedByAnyFlowTest(): void
    {
        $registry = $this->registry();
        $covered = $this->coveredOps();

        foreach (array_keys($registry) as $op) {
            self::assertNotContains(
                $op,
                $covered,
                sprintf(
                    '%s is listed in skipped-operations.json but a flow test now exercises it via #[Alps(\'%s\')]. '
                    . 'Remove it from docs/eccube-spec-coverage/skipped-operations.json.',
                    $op,
                    $op,
                ),
            );
        }
    }

    public function testEveryDoOpIsEitherSkippedOrCovered(): void
    {
        $registry = $this->registry();
        $covered = $this->coveredOps();
        $skipped = array_keys($registry);

        $uncovered = [];
        foreach ($this->doOps() as $op) {
            if (! in_array($op, $covered, true) && ! in_array($op, $skipped, true)) {
                $uncovered[] = $op;
            }
        }

        self::assertSame(
            [],
            $uncovered,
            sprintf(
                'These alps.json do* unsafe|idempotent operations are neither covered by a flow test nor in the skip '
                . 'registry: %s. Either cover them with a #[Alps(\'op\')] flow test, or add them to '
                . 'docs/eccube-spec-coverage/skipped-operations.json with a category, reason and evidence.',
                implode(', ', $uncovered),
            ),
        );
    }

    public function testRegistryDoesNotListUnknownOps(): void
    {
        $registry = $this->registry();
        $doOps = $this->doOps();

        foreach (array_keys($registry) as $op) {
            self::assertContains(
                $op,
                $doOps,
                sprintf(
                    '%s is in the skip registry but is not an alps.json do* unsafe|idempotent descriptor. '
                    . 'Remove the stale entry or fix the op id.',
                    $op,
                ),
            );
        }
    }
}
