<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminProductCsvExported;
use MyVendor\BeMart\Be\Input\AdminExportProductInput;
use MyVendor\BeMart\Be\Reason\Entity\CsvColumnConfigEntity;
use MyVendor\BeMart\Be\Reason\Query\CsvColumnConfigStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;
use function strtok;
use function trim;

final class AdminProductCsvExportedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    /**
     * Build a Becoming whose CsvColumnConfigStorage returns a fixed
     * saved configuration (the Fake query fixture is empty, so the
     * default-column path is what setUp exercises).
     *
     * @param list<CsvColumnConfigEntity> $config
     */
    private function becomingWithConfig(string $adminId, array $config): BecomingInterface
    {
        $session = new FakeAdminSession($adminId);
        $storage = new class ($config) implements CsvColumnConfigStorageInterface {
            /** @param list<CsvColumnConfigEntity> $config */
            public function __construct(private readonly array $config)
            {
            }

            public function listByType(int $csvType): array
            {
                return $this->config;
            }

            public function replaceType(int $csvType, \MyVendor\BeMart\Be\Reason\Query\Param\CsvColumnConfigList $entries): void
            {
            }
        };

        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session, $storage) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly CsvColumnConfigStorageInterface $storage,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
                $this->bind(CsvColumnConfigStorageInterface::class)->toInstance($this->storage);
            }
        };
        $base->override($override);

        return (new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test'))
            ->getInstance(BecomingInterface::class);
    }

    public function testCsvIncludesHeaderAndAllProducts(): void
    {
        $final = ($this->becoming)(new AdminExportProductInput());

        $this->assertInstanceOf(AdminProductCsvExported::class, $final);
        $this->assertGreaterThanOrEqual(5, $final->count);

        $this->assertStringContainsString('productCode,productName', $final->csv);
        $this->assertStringContainsString('sample-001', $final->csv);
        $this->assertStringContainsString('admin-active-001', $final->csv);
        // Even withdrawn rows are in the export — admin scope.
        $this->assertStringContainsString('admin-withdrawn-001', $final->csv);
    }

    public function testNoAdminSessionRaisesUnauthorizedAdmin(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new AdminExportProductInput());
    }

    public function testSavedConfigurationReordersAndNarrowsTheColumns(): void
    {
        // Admin saved (via doUpdateCsv): productName first, then
        // productCode; everything else disabled / omitted.
        $becoming = $this->becomingWithConfig(self::TEST_ADMIN_ID, [
            new CsvColumnConfigEntity(csvType: 3, columnName: 'productName', enabled: true, sortNo: 10),
            new CsvColumnConfigEntity(csvType: 3, columnName: 'productCode', enabled: true, sortNo: 20),
            new CsvColumnConfigEntity(csvType: 3, columnName: 'note', enabled: false, sortNo: 30),
        ]);

        $final = $becoming(new AdminExportProductInput());

        $this->assertInstanceOf(AdminProductCsvExported::class, $final);
        // Header is reordered to the saved sortNo order and narrowed to
        // the two enabled columns — the default vector started with
        // productCode,productName,price02,…
        $header = trim((string) strtok($final->csv, "\n"));
        $this->assertSame('productName,productCode', $header);
        // The disabled / dropped columns no longer head the rows.
        $this->assertStringNotContainsString('price02', $final->csv);
    }

    public function testConfigEnablingOnlyUnknownColumnsFallsBackToFullExport(): void
    {
        // A stale configuration that names only columns this Final does
        // not encode must not yield an empty export — fall back to the
        // full default vector.
        $becoming = $this->becomingWithConfig(self::TEST_ADMIN_ID, [
            new CsvColumnConfigEntity(csvType: 3, columnName: 'sinceDeletedColumn', enabled: true, sortNo: 1),
        ]);

        $final = $becoming(new AdminExportProductInput());

        $header = trim((string) strtok($final->csv, "\n"));
        $this->assertSame('productCode,productName,price02,stock,productStatus,description,searchWord,note', $header);
    }
}
