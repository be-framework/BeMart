<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use BEAR\Dev\Http\AbstractWorkflowTest;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\Depends;

use function assert;
use function bin2hex;
use function is_string;
use function random_bytes;

class FlowAdminCsvExchangeTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-csv-exchange';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-csv-csrf-token';

    private static WorkflowDbSession|null $dbSession = null;
    private static string $csvClassName;
    private static string $csvClassNameId;
    private static string $csvClassCategory;

    public static function setUpBeforeClass(): void
    {
        self::$dbSession = WorkflowDbSession::startForAdmin(self::ADMIN_ID, self::CSRF_TOKEN);
    }

    public static function tearDownAfterClass(): void
    {
        self::$dbSession?->restore();
        self::$dbSession = null;

        parent::tearDownAfterClass();
    }

    protected function newResource(): ResourceInterface
    {
        assert(self::$dbSession instanceof WorkflowDbSession);

        return self::$dbSession->resource();
    }

    #[Alps('goCsv')]
    public function testCsvConfig(): ResourceObject
    {
        $response = $this->resource->get('page://self/admin/csv-config');

        $this->assertSame(Code::OK, $response->code);

        return $response;
    }

    #[Alps('doUpdateCsv')]
    #[Depends('testCsvConfig')]
    public function testUpdatesCsvConfig(ResourceObject $response): ResourceObject
    {
        $updated = $this->resource->post($this->linkHref($response, 'doUpdateCsv'), [
            'csvType' => 1,
            'columns' => [
                ['columnName' => 'productCode', 'enabled' => true, 'sortNo' => 1],
                ['columnName' => 'productName', 'enabled' => true, 'sortNo' => 2],
                ['columnName' => 'price', 'enabled' => true, 'sortNo' => 3],
            ],
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $updated->code);
        $this->assertSame(1, $this->bodyValue($updated, 'csvType'));
        $this->assertSame(3, $this->bodyValue($updated, 'count'));

        return $updated;
    }

    #[Alps('goExportProduct')]
    #[Depends('testUpdatesCsvConfig')]
    public function testExportsProductCsv(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goExportProduct');
    }

    #[Alps('doImportProductCsv')]
    #[Depends('testExportsProductCsv')]
    public function testImportsProductCsv(ResourceObject $response): ResourceObject
    {
        $productCode = 'workflow-csv-product-' . bin2hex(random_bytes(4));
        $imported = $this->resource->post($this->linkHref($response, 'doImportProductCsv'), [
            'csv' => "productCode,productName,price02,stock,productStatus,description,searchWord,note\n{$productCode},Workflow CSV Product,1200,5,1,CSV import product,workflow csv,Imported by workflow\n",
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $imported->code);
        $this->assertSame('doImportProductCsv', $this->bodyValue($imported, 'transitionId'));
        $this->assertSame(1, $this->bodyValue($imported, 'count'));

        return $imported;
    }

    #[Alps('goExportCategory')]
    #[Depends('testExportsProductCsv')]
    public function testExportsCategoryCsv(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goExportCategory');
    }

    #[Alps('doImportCategoryCsv')]
    #[Depends('testExportsCategoryCsv')]
    public function testImportsCategoryCsv(ResourceObject $response): ResourceObject
    {
        $imported = $this->resource->post($this->linkHref($response, 'doImportCategoryCsv'), [
            'csv' => "category_id,category_name,parent_category_id\n,Workflow Category,\n",
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $imported->code);
        $this->assertSame('doImportCategoryCsv', $this->bodyValue($imported, 'transitionId'));

        return $imported;
    }

    #[Alps('goExportOrder')]
    #[Depends('testImportsCategoryCsv')]
    public function testExportsOrderCsv(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goExportOrder');
    }

    #[Alps('goExportShipping')]
    #[Depends('testExportsOrderCsv')]
    public function testExportsShippingCsv(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goExportShipping');
    }

    #[Alps('doImportShippingCsv')]
    #[Depends('testExportsShippingCsv')]
    public function testImportsShippingCsv(ResourceObject $response): ResourceObject
    {
        $imported = $this->resource->post($this->linkHref($response, 'doImportShippingCsv'), [
            'csv' => "shipping_id,tracking_number,ship_date\n1,TRACK-WORKFLOW,2026-06-05\n",
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $imported->code);
        $this->assertSame('doImportShippingCsv', $this->bodyValue($imported, 'transitionId'));

        return $imported;
    }

    #[Alps('goExportCustomer')]
    #[Depends('testImportsShippingCsv')]
    public function testExportsCustomerCsv(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goExportCustomer');
    }

    #[Alps('goExportClassName')]
    #[Depends('testExportsCustomerCsv')]
    public function testExportsClassNameCsv(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goExportClassName');
    }

    #[Alps('doImportClassNameCsv')]
    #[Depends('testExportsClassNameCsv')]
    public function testImportsClassNameCsv(ResourceObject $response): ResourceObject
    {
        self::$csvClassName = 'WF CSV Class ' . bin2hex(random_bytes(4));
        $imported = $this->resource->post($this->linkHref($response, 'doImportClassNameCsv'), [
            'csv' => "class_name_id,class_name,backend_name\n," . self::$csvClassName . ',' . self::$csvClassName . "\n",
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $imported->code);
        $this->assertSame('doImportClassNameCsv', $this->bodyValue($imported, 'transitionId'));
        $this->assertSame(1, $this->bodyValue($imported, 'accepted'));

        return $imported;
    }

    #[Alps('goClassNameList')]
    #[Depends('testImportsClassNameCsv')]
    public function testFindsImportedClassNameCsv(ResourceObject $response): ResourceObject
    {
        $list = $this->followLocation($response, '/admin/class-name/class-name-list');
        $classNames = $this->bodyValue($list, 'classNames');
        $this->assertIsArray($classNames);
        foreach ($classNames as $className) {
            $this->assertIsArray($className);
            if (($className['name'] ?? null) !== self::$csvClassName) {
                continue;
            }

            $classNameId = $className['classNameId'] ?? null;
            $this->assertIsString($classNameId);
            self::$csvClassNameId = $classNameId;

            return $response;
        }

        $this->fail('Imported class name was not visible in class-name list.');
    }

    #[Alps('goExportClassCategory')]
    #[Depends('testFindsImportedClassNameCsv')]
    public function testExportsClassCategoryCsv(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goExportClassCategory');
    }

    #[Alps('doImportClassCategoryCsv')]
    #[Depends('testExportsClassCategoryCsv')]
    public function testImportsClassCategoryCsv(ResourceObject $response): ResourceObject
    {
        self::$csvClassCategory = 'WF CSV CC ' . bin2hex(random_bytes(4));
        $imported = $this->resource->post($this->linkHref($response, 'doImportClassCategoryCsv'), [
            'csv' => "class_category_id,class_name_id,class_category_name,backend_name\n," . self::$csvClassNameId . ',' . self::$csvClassCategory . ',' . self::$csvClassCategory . "\n",
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::OK, $imported->code);
        $this->assertSame('doImportClassCategoryCsv', $this->bodyValue($imported, 'transitionId'));
        $this->assertSame(1, $this->bodyValue($imported, 'accepted'));

        return $imported;
    }

    #[Alps('goClassCategoryList')]
    #[Depends('testImportsClassCategoryCsv')]
    public function testFindsImportedClassCategoryCsv(ResourceObject $response): void
    {
        $list = $this->followLocation($response, '/admin/class-category/class-category-list');
        $classCategories = $this->bodyValue($list, 'classCategories');
        $this->assertIsArray($classCategories);
        foreach ($classCategories as $classCategory) {
            $this->assertIsArray($classCategory);
            if (($classCategory['name'] ?? null) !== self::$csvClassCategory) {
                continue;
            }

            $classNameId = $classCategory['classNameId'] ?? null;
            $this->assertTrue(is_string($classNameId) && $classNameId === self::$csvClassNameId);

            return;
        }

        $this->fail('Imported class category was not visible in class-category list.');
    }
}
