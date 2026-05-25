<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminCsvUploadForm;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-layer coverage for the four admin CSV-upload Product Tier-2
 * pages — `csv_product`, `csv_category`, `csv_class_name`,
 * `csv_class_category`.
 *
 * The four resources share {@see \MyVendor\BeMart\Resource\Page\Admin\Product\AbstractCsvUpload}:
 * a thin Pattern-B GET renderer for EC-CUBE's `csv_*.twig` upload
 * screens. Each renders a CSV-upload form + a format-description table
 * with no Be transition invoked; the AUTHZ guard rejects anonymous
 * admins.
 */
final class AdminProductCsvUploadResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** @return list<array{string, string}> uri, expected csvTitle */
    public static function csvPageProvider(): array
    {
        return [
            ['page://self/admin/product/csv-product', '商品CSV登録'],
            ['page://self/admin/product/csv-category', 'カテゴリCSV登録'],
            ['page://self/admin/product/csv-class-name', '規格CSV登録'],
            ['page://self/admin/product/csv-class-category', '規格分類CSV登録'],
        ];
    }

    private function resource(string|null $adminId): ResourceInterface
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

        return $injector->getInstance(ResourceInterface::class);
    }

    /** @dataProvider csvPageProvider */
    public function testOnGetRendersUploadScreen(string $uri, string $expectedTitle): void
    {
        $ro = $this->resource(self::TEST_ADMIN_ID)->get($uri);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminCsvUploadForm::class, $ro->body['form']);
        $this->assertSame($expectedTitle, $ro->body['csvTitle']);
        $this->assertNotEmpty($ro->body['columns']);
    }

    /** @dataProvider csvPageProvider */
    public function testOnGetRejectsAnonymousAdmin(string $uri): void
    {
        $ro = $this->resource(null)->get($uri);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
