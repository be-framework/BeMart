<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Form\AdminProductClassForm;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-layer coverage for the admin 商品規格 Product Tier-2 page.
 *
 * The resource is a thin GET renderer for EC-CUBE's
 * `Product/product_class.twig` matrix editor. It renders a blank
 * "新規規格" editor (works with empty Fake storage — the Be domain has
 * no transition to read a product's ProductClass matrix). The AUTHZ
 * guard rejects anonymous admins.
 */
final class AdminProductClassResourceTest extends TestCase
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

    public function testOnGetRendersBlankMatrixEditor(): void
    {
        $ro = $this->resource->get('page://self/admin/product/product-class', ['productCode' => 'admin-active-001']);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminProductClassForm::class, $ro->body['form']);
        $this->assertSame('admin-active-001', $ro->body['productCode']);
        $this->assertSame([], $ro->body['classes']);
    }

    public function testOnGetWithoutProductCodeRendersEditor(): void
    {
        $ro = $this->resource->get('page://self/admin/product/product-class');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('', $ro->body['productCode']);
    }

    public function testOnGetRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/product/product-class', ['productCode' => 'admin-active-001']);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }

    public function testRegisterReturns201(): void
    {
        // EXACT browser field names: the AdminProductClassForm leaf SKU
        // (`product_code`) plus snake_case price/stock/unlimited/fee.
        $ro = $this->resource->post('page://self/admin/product/product-class', [
            'productCode' => 'admin-active-001',
            'product_code' => 'admin-active-001-2',
            'price02' => 1200,
            'stock' => 10,
            'stock_unlimited' => false,
            'delivery_fee' => 0,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('admin-active-001', $ro->body['productCode']);
        // Distinct allocated id (Fake fixture), not a hardcoded 1.
        $this->assertSame('pc-fake-next', $ro->body['productClassId']);
    }

    public function testRegisterRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->post('page://self/admin/product/product-class', [
            'productCode' => 'admin-active-001',
            'price02' => 1200,
            'stock' => 10,
            'stock_unlimited' => false,
            'delivery_fee' => 0,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }
}
