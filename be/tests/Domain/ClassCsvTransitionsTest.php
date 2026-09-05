<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\ClassCategoryCsvExported;
use MyVendor\BeMart\Be\Final\ClassCategoryCsvImported;
use MyVendor\BeMart\Be\Final\ClassNameCsvExported;
use MyVendor\BeMart\Be\Final\ClassNameCsvImported;
use MyVendor\BeMart\Be\Input\ExportClassCategoryInput;
use MyVendor\BeMart\Be\Input\ExportClassNameInput;
use MyVendor\BeMart\Be\Input\ImportClassCategoryCsvInput;
use MyVendor\BeMart\Be\Input\ImportClassNameCsvInput;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\RecordingProductCacheInvalidator;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\ProductCacheInvalidatorInterface;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class ClassCsvTransitionsTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private BecomingInterface $becoming;
    private RecordingProductCacheInvalidator $invalidator;

    protected function setUp(): void
    {
        $this->build(self::TEST_ADMIN_ID);
    }

    private function build(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $this->invalidator = new RecordingProductCacheInvalidator();
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class ($session, $this->invalidator) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly RecordingProductCacheInvalidator $invalidator,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
                $this->bind(ProductCacheInvalidatorInterface::class)->toInstance($this->invalidator);
            }
        });

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testExportClassName(): void
    {
        $final = ($this->becoming)(new ExportClassNameInput());
        $this->assertInstanceOf(ClassNameCsvExported::class, $final);
        $this->assertStringContainsString('規格名', $final->document->content);
        $this->assertGreaterThan(0, $final->document->size);
    }

    public function testExportClassCategory(): void
    {
        $final = ($this->becoming)(new ExportClassCategoryInput(classNameId: 'cn-color'));
        $this->assertInstanceOf(ClassCategoryCsvExported::class, $final);
        $this->assertStringContainsString('規格分類', $final->document->content);
    }

    public function testImportClassName(): void
    {
        $final = ($this->becoming)(new ImportClassNameCsvInput(csv: "規格名ID,規格名\r\ncn-x,新規格\r\n"));
        $this->assertInstanceOf(ClassNameCsvImported::class, $final);
        $this->assertSame(1, $final->accepted);
        $this->assertSame(1, $this->invalidator->calls);
    }

    public function testImportClassCategory(): void
    {
        $final = ($this->becoming)(new ImportClassCategoryCsvInput(csv: "規格分類ID,規格名ID,規格分類名\r\ncc-x,cn-color,新分類\r\ncc-y,cn-color,別分類\r\n"));
        $this->assertInstanceOf(ClassCategoryCsvImported::class, $final);
        $this->assertSame(2, $final->accepted);
        $this->assertSame(1, $this->invalidator->calls);
    }

    public function testExportRefusesAnonymous(): void
    {
        $this->build(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new ExportClassNameInput());
    }

    public function testImportRefusesAnonymous(): void
    {
        $this->build(null);
        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new ImportClassNameCsvInput(csv: 'x'));
    }
}
