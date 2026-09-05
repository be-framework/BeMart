<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use MyVendor\BeMart\Be\Reason\Csv\CsvColumnLayout;
use MyVendor\BeMart\Be\Reason\Entity\CsvColumnConfigEntity;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for {@see CsvColumnLayout} — the doUpdateCsv
 * consumption logic that overlays a saved column configuration on an
 * export Final's default column vector.
 */
final class CsvColumnLayoutTest extends TestCase
{
    /** @var list<string> */
    private const DEFAULTS = ['a', 'b', 'c', 'd'];

    private function entity(string $name, bool $enabled, int $sortNo): CsvColumnConfigEntity
    {
        return new CsvColumnConfigEntity(csvType: 3, columnName: $name, enabled: $enabled, sortNo: $sortNo);
    }

    public function testEmptyConfigEmitsEveryDefaultColumnInOrder(): void
    {
        $layout = CsvColumnLayout::resolve(self::DEFAULTS, []);

        $this->assertSame(['a', 'b', 'c', 'd'], $layout->columns);
    }

    public function testConfigSelectsOnlyEnabledColumns(): void
    {
        $layout = CsvColumnLayout::resolve(self::DEFAULTS, [
            $this->entity('a', true, 1),
            $this->entity('b', false, 2),
            $this->entity('c', true, 3),
            $this->entity('d', false, 4),
        ]);

        $this->assertSame(['a', 'c'], $layout->columns);
    }

    public function testConfigReordersBySortNo(): void
    {
        // Deliberately out of sortNo order — resolve must sort.
        $layout = CsvColumnLayout::resolve(self::DEFAULTS, [
            $this->entity('c', true, 30),
            $this->entity('a', true, 10),
            $this->entity('b', true, 20),
        ]);

        $this->assertSame(['a', 'b', 'c'], $layout->columns);
    }

    public function testUnknownColumnsAreDropped(): void
    {
        // 'x' is not in the default vector — the Final cannot encode it,
        // so it must not appear in the output shape.
        $layout = CsvColumnLayout::resolve(self::DEFAULTS, [
            $this->entity('a', true, 1),
            $this->entity('x', true, 2),
            $this->entity('b', true, 3),
        ]);

        $this->assertSame(['a', 'b'], $layout->columns);
    }

    public function testConfigThatEnablesNothingKnownFallsBackToDefault(): void
    {
        // Every enabled column is unknown / every known column disabled
        // → a zero-column export is never intended, fall back to full.
        $layout = CsvColumnLayout::resolve(self::DEFAULTS, [
            $this->entity('a', false, 1),
            $this->entity('x', true, 2),
        ]);

        $this->assertSame(['a', 'b', 'c', 'd'], $layout->columns);
    }

    public function testProjectReturnsCellsInLayoutOrder(): void
    {
        $layout = CsvColumnLayout::resolve(self::DEFAULTS, [
            $this->entity('c', true, 1),
            $this->entity('a', true, 2),
        ]);

        $projected = $layout->project(['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D']);

        $this->assertSame(['C', 'A'], $projected);
    }

    public function testProjectNeutralizesFormulaCellsButKeepsNumbers(): void
    {
        $layout = CsvColumnLayout::resolve(self::DEFAULTS, []);

        $projected = $layout->project([
            'a' => "+cmd|' /C calc'!A0",
            'b' => '=1+1',
            'c' => '@SUM(A1:A9)',
            'd' => "\tHYPERLINK(\"http://evil.example\")",
        ]);

        $this->assertSame([
            "'+cmd|' /C calc'!A0",
            "'=1+1",
            "'@SUM(A1:A9)",
            "'\tHYPERLINK(\"http://evil.example\")",
        ], $projected);
    }

    public function testProjectLeavesNegativeNumbersImportableAsNumbers(): void
    {
        $layout = CsvColumnLayout::resolve(self::DEFAULTS, []);

        // A leading '-' is a formula trigger, but a real number is not a
        // formula — prefixing it would import as text and break totals.
        $projected = $layout->project(['a' => '-1200', 'b' => -3, 'c' => '-0.5', 'd' => '-cmd']);

        $this->assertSame(['-1200', -3, '-0.5', "'-cmd"], $projected);
    }
}
