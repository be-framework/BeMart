<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class AdminOrderPdfExportResourceTest extends TestCase
{
    private const ORDER_NO = 'past0000000000000000000000000001';
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->resource = $this->buildResource(self::TEST_ADMIN_ID);
    }

    public function testExportOrderPdfReturns501NotImplemented(): void
    {
        $ro = $this->resource->get('page://self/admin/order/export-order-pdf', [
            'orderNos' => [self::ORDER_NO],
        ]);

        $this->assertSame(501, $ro->code);
        $this->assertStringContainsString('納品書PDF出力はこのビルドでは利用できません', $ro->body['message']);
    }

    public function testExportOrderPdfLegacyParamAlsoReturns501(): void
    {
        $ro = $this->resource->get('page://self/admin/order/export-order-pdf', [
            'orderNo' => self::ORDER_NO,
        ]);

        $this->assertSame(501, $ro->code);
        $this->assertStringContainsString('納品書PDF出力はこのビルドでは利用できません', $ro->body['message']);
    }

    public function testExportOrderPdfUnknownReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\OrderNotFoundException::class);

        $this->resource->get('page://self/admin/order/export-order-pdf', [
            'orderNos' => ['never00000000000000000000000000z'],
        ]);
    }

    public function testExportOrderPdfEmptyOrderNosReturns400(): void
    {
        $ro = $this->resource->get('page://self/admin/order/export-order-pdf', [
            'orderNos' => [],
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
        $this->assertStringContainsString('注文番号リスト', $ro->body['message']);
    }

    public function testExportOrderPdfWithoutAdminReturns403(): void
    {
        $this->resource = $this->buildResource(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->get('page://self/admin/order/export-order-pdf', [
            'orderNos' => [self::ORDER_NO],
        ]);
    }

    private function buildResource(string|null $adminId): ResourceInterface
    {
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($adminId) extends AbstractModule {
            public function __construct(private readonly string|null $adminId)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance(new FakeAdminSession($this->adminId));
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');

        return $injector->getInstance(ResourceInterface::class);
    }
}
