<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Smoke;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Throwable;

use function array_diff;
use function array_values;
use function count;
use function file_exists;
use function sprintf;

#[CoversNothing]
final class MediaQuerySqlSmokeTest extends TestCase
{
    /** @return array<string, array{0: string, 1: ReflectionMethod}> */
    public static function mediaQuerySqlCases(): array
    {
        return MediaQuerySqlSmokeHelper::cases();
    }

    public function testEverySqlFileHasAMediaQuerySmokeCase(): void
    {
        self::assertSame(
            [],
            array_values(array_diff(MediaQuerySqlSmokeHelper::sqlIds(), array_keys(self::mediaQuerySqlCases()))),
            'var/sql/*.sql without DB-backed MediaQuery smoke case',
        );
    }

    #[DataProvider('mediaQuerySqlCases')]
    public function testSqlFilePreparesWithMediaQueryBinding(string $sqlId, ReflectionMethod $method): void
    {
        try {
            $connection = MediaQuerySqlSmokeHelper::connection();
        } catch (Throwable $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $sqlFile = MediaQuerySqlSmokeHelper::sqlFile($sqlId);
        self::assertTrue(file_exists($sqlFile), sprintf('Missing SQL file for #[DbQuery("%s")]', $sqlId));

        $values = MediaQuerySqlSmokeHelper::convertedValues($method);
        $statements = MediaQuerySqlSmokeHelper::statements($sqlFile);
        self::assertGreaterThan(0, count($statements), sprintf('Empty SQL file: %s', $sqlFile));

        foreach ($statements as $index => $statement) {
            try {
                // This intentionally stops at DB-native prepare + Aura binding.
                // It matches Ray.MediaQuery's placeholder contract without
                // executing DML, so the smoke stays out of affected-row and
                // workflow-fixture behavior assertions.
                $connection->prepareWithValues($statement, $values);
            } catch (Throwable $e) {
                self::fail(sprintf(
                    'MediaQuery SQL smoke failed for %s (%s::%s, statement %d/%d): %s',
                    $sqlId,
                    $method->getDeclaringClass()->getName(),
                    $method->getName(),
                    $index + 1,
                    count($statements),
                    $e->getMessage(),
                ));
            }
        }

        self::addToAssertionCount(1);
    }
}
