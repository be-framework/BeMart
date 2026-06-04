<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Injector;
use MyVendor\BeMart\Tests\Support\Hypermedia\AbstractWorkflowTest;
use PHPUnit\Framework\Attributes\Depends;

use function assert;

class FlowAdminCsvExchangeTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-admin-csv-exchange';

    protected function newResource(): ResourceInterface
    {
        $resource = Injector::getInstance('test-hal-api-app')->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    #[Alps('goCsv')]
    public function testCsvConfig(): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-csv-exchange open CSV config.');
    }

    #[Alps('doUpdateCsv')]
    #[Depends('testCsvConfig')]
    public function testUpdatesCsvConfig(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-csv-exchange update CSV config.');
    }

    #[Alps('goExportProduct')]
    #[Depends('testUpdatesCsvConfig')]
    public function testExportsProductCsv(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-csv-exchange export product CSV.');
    }

    #[Alps('doImportProductCsv')]
    #[Depends('testExportsProductCsv')]
    public function testImportsProductCsv(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-csv-exchange import product CSV.');
    }

    #[Alps('goExportCategory')]
    #[Depends('testImportsProductCsv')]
    public function testExportsCategoryCsv(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-csv-exchange export category CSV.');
    }

    #[Alps('doImportCategoryCsv')]
    #[Depends('testExportsCategoryCsv')]
    public function testImportsCategoryCsv(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-csv-exchange import category CSV.');
    }

    #[Alps('goExportOrder')]
    #[Depends('testImportsCategoryCsv')]
    public function testExportsOrderCsv(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-csv-exchange export order CSV.');
    }

    #[Alps('goExportShipping')]
    #[Depends('testExportsOrderCsv')]
    public function testExportsShippingCsv(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-csv-exchange export shipping CSV.');
    }

    #[Alps('doImportShippingCsv')]
    #[Depends('testExportsShippingCsv')]
    public function testImportsShippingCsv(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-csv-exchange import shipping CSV.');
    }

    #[Alps('goExportCustomer')]
    #[Depends('testImportsShippingCsv')]
    public function testExportsCustomerCsv(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-csv-exchange export customer CSV.');
    }

    #[Alps('goExportClassName')]
    #[Depends('testExportsCustomerCsv')]
    public function testExportsClassNameCsv(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-csv-exchange export class name CSV.');
    }

    #[Alps('doImportClassNameCsv')]
    #[Depends('testExportsClassNameCsv')]
    public function testImportsClassNameCsv(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-csv-exchange import class name CSV.');
    }

    #[Alps('goExportClassCategory')]
    #[Depends('testImportsClassNameCsv')]
    public function testExportsClassCategoryCsv(ResourceObject $response): ResourceObject
    {
        self::markTestIncomplete('TODO: flow-admin-csv-exchange export class category CSV.');
    }

    #[Alps('doImportClassCategoryCsv')]
    #[Depends('testExportsClassCategoryCsv')]
    public function testImportsClassCategoryCsv(ResourceObject $response): void
    {
        self::markTestIncomplete('TODO: flow-admin-csv-exchange import class category CSV.');
    }
}
