<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminProductEditForm;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-layer coverage for the admin 商品登録 / 商品編集 Product Tier-2 page.
 *
 * The resource is a thin GET renderer for EC-CUBE's `Product/product.twig`
 * multi-tab editor. An empty productCode renders a blank "new product"
 * editor (works with empty Fake storage); a known productCode pre-fills;
 * an unknown productCode is 404. The AUTHZ guard rejects anonymous
 * admins.
 */
final class AdminProductEditResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const SEEDED_PRODUCT_CODE = 'admin-active-001';

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

    public function testOnGetNewRendersBlankEditor(): void
    {
        $ro = $this->resource->get('page://self/admin/product/edit');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminProductEditForm::class, $ro->body['form']);
        $this->assertSame('', $ro->body['productCode']);
        $this->assertNull($ro->body['product']);
    }

    public function testOnGetKnownProductPreFillsEditor(): void
    {
        $ro = $this->resource->get('page://self/admin/product/edit', ['productCode' => self::SEEDED_PRODUCT_CODE]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminProductEditForm::class, $ro->body['form']);
        $this->assertSame(self::SEEDED_PRODUCT_CODE, $ro->body['productCode']);
        $this->assertSame('管理画面用 商品A', $ro->body['product']['productName']);
    }

    public function testOnGetUnknownProductReturns404(): void
    {
        $ro = $this->resource->get('page://self/admin/product/edit', ['productCode' => 'does-not-exist-zzz']);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnGetRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/product/edit');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
