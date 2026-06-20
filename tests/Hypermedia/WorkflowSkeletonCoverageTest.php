<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function class_exists;

final class WorkflowSkeletonCoverageTest extends TestCase
{
    /** @var array<string, non-empty-string> */
    private const TRUE_FLOW_CLASSES = [
        'flow-admin-product-publish' => 'FlowAdminProductPublishTest',
        'flow-customer-purchase' => 'FlowCustomerPurchaseTest',
        'flow-admin-order-fulfillment' => 'FlowAdminOrderFulfillmentTest',
        'flow-customer-registration' => 'FlowCustomerRegistrationTest',
        'flow-customer-account-maintenance' => 'FlowCustomerAccountMaintenanceTest',
        'flow-customer-inquiry' => 'FlowCustomerInquiryTest',
        'flow-admin-content-publish' => 'FlowAdminContentPublishTest',
        'flow-admin-shop-configuration' => 'FlowAdminShopConfigurationTest',
        'flow-admin-system-operation' => 'FlowAdminSystemOperationTest',
        'flow-admin-csv-exchange' => 'FlowAdminCsvExchangeTest',
        'flow-admin-master-data-update' => 'FlowAdminMasterDataUpdateTest',
        'flow-admin-template-lifecycle' => 'FlowAdminTemplateLifecycleTest',
        'flow-admin-mail-template-maintenance' => 'FlowAdminMailTemplateMaintenanceTest',
    ];

    public function testEveryTrueFlowHasHypermediaAndHttpProjection(): void
    {
        self::assertCount(13, self::TRUE_FLOW_CLASSES);

        foreach (self::TRUE_FLOW_CLASSES as $flowId => $className) {
            $hypermediaClass = __NAMESPACE__ . '\\' . $className;
            $httpClass = 'MyVendor\\BeMart\\Tests\\Http\\' . $className;

            self::assertTrue(class_exists($hypermediaClass), "{$flowId} is missing a Hypermedia workflow class.");
            self::assertTrue(class_exists($httpClass), "{$flowId} is missing an HTTP workflow projection.");

            $hypermedia = new ReflectionClass($hypermediaClass);
            self::assertTrue($hypermedia->hasConstant('FLOW_ID'), "{$hypermediaClass} must declare FLOW_ID.");
            self::assertSame($flowId, $hypermedia->getConstant('FLOW_ID'));

            $http = new ReflectionClass($httpClass);
            self::assertTrue($http->isSubclassOf($hypermediaClass), "{$httpClass} must extend {$hypermediaClass}.");
        }
    }

    public function testEveryTrueFlowHasHtmlProjection(): void
    {
        self::assertCount(13, self::TRUE_FLOW_CLASSES);

        foreach (self::TRUE_FLOW_CLASSES as $flowId => $className) {
            // The HTML projection is a standalone walk (it resolves affordances
            // from rendered class/rel, not HAL), so it does NOT extend the
            // Hypermedia class — only same-named, in Tests\Html, with a FLOW_ID.
            $htmlClass = 'MyVendor\\BeMart\\Tests\\Html\\' . $className;

            self::assertTrue(class_exists($htmlClass), "{$flowId} is missing an HTML workflow projection.");

            $html = new ReflectionClass($htmlClass);
            self::assertTrue($html->hasConstant('FLOW_ID'), "{$htmlClass} must declare FLOW_ID.");
        }
    }
}
