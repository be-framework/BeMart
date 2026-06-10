<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;
use function is_string;

/**
 * Resource coverage for the 規格名/規格分類 CSV export (download) and import
 * (upload) admin pages.
 */
final class AdminClassCsvResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testExportClassNameDownload(): void
    {
        $ro = $this->resource->get('page://self/admin/class-name/class-name-export');

        $this->assertSame(Code::OK, $ro->code);
        // The download filename must name the class-name export specifically
        // (regression guard for issue #30: the two exports must not swap
        // their Content-Disposition filenames).
        $this->assertSame('attachment; filename="class_name.csv"', $ro->headers['Content-Disposition']);
        $this->assertTrue(is_string($ro->body));
    }

    public function testExportClassCategoryDownload(): void
    {
        $ro = $this->resource->get('page://self/admin/class-category/class-category-export', ['classNameId' => 'cn-color']);

        $this->assertSame(Code::OK, $ro->code);
        // Must be class_category.csv, NOT class_name.csv (issue #30).
        $this->assertSame('attachment; filename="class_category.csv"', $ro->headers['Content-Disposition']);
    }

    public function testExportAnonymousReturns403(): void
    {
        $this->rebindAdminSession(null);
        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->get('page://self/admin/class-name/class-name-export');
    }

    public function testImportClassNameCsv(): void
    {
        $ro = $this->resource->post('page://self/admin/product/csv-class-name', [
            'csv' => "規格名ID,規格名\r\ncn-x,新\r\n",
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doImportClassNameCsv', $ro->body['transitionId']);
        $this->assertSame(1, $ro->body['accepted']);
    }

    public function testImportClassCategoryCsv(): void
    {
        $ro = $this->resource->post('page://self/admin/product/csv-class-category', [
            'csv' => "規格分類ID,規格名ID,規格分類名\r\ncc-x,cn-color,新\r\n",
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doImportClassCategoryCsv', $ro->body['transitionId']);
    }

    public function testImportGetRendersUploadShell(): void
    {
        $ro = $this->resource->get('page://self/admin/product/csv-class-name');
        $this->assertSame(Code::OK, $ro->code);
        $this->assertArrayHasKey('csvTitle', $ro->body);
    }
}
