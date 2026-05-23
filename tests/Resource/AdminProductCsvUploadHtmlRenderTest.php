<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\HtmlModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Phase 3 — HTML render check for the four admin CSV-upload Product
 * Tier-2 pages (`csv_product`, `csv_category`, `csv_class_name`,
 * `csv_class_category`).
 *
 * Render-smoke standard: each page renders a full admin-frame HTML
 * document through {@see HtmlModule} with the body shape supplied by
 * the {@see \MyVendor\BeMart\Resource\Page\Admin\Product\AbstractCsvUpload}
 * subclasses. The EC-CUBE residual-diff fidelity check is a follow-up
 * gated on the EC-CUBE 4.3 reference clone (`tools/ec-cube-source/`).
 */
final class AdminProductCsvUploadHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    /** @return list<array{string, string}> uri, page-specific form-id marker */
    public static function csvPageProvider(): array
    {
        return [
            ['page://self/admin/product/csv-product', 'id="csv_product_form"'],
            ['page://self/admin/product/csv-category', 'id="csv_category_form"'],
            ['page://self/admin/product/csv-class-name', 'id="csv_class_name_form"'],
            ['page://self/admin/product/csv-class-category', 'id="csv_class_category_form"'],
        ];
    }

    protected function setUp(): void
    {
        $module = new HtmlModule(new Meta('MyVendor\\BeMart', 'html'));
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $module->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        });
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/html');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    /** @dataProvider csvPageProvider */
    public function testCsvUploadRendersAsHtmlDocument(string $uri, string $formIdMarker): void
    {
        $ro = $this->resource->get($uri);

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertStringContainsString('<header class="c-headerBar">', $html);
        $this->assertStringContainsString('<div class="c-contentsArea">', $html);
        $this->assertStringContainsString($formIdMarker, $html);
        $this->assertStringContainsString('CSVファイルフォーマット', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }
}
