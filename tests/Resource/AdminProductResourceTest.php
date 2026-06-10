<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;
use function getenv;
use function putenv;

final class AdminProductResourceTest extends TestCase
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
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // ----------- onGet (admin variant goProduct) -----------

    public function testOnGetReturnsAdminProductDetail(): void
    {
        $ro = $this->resource->get('page://self/admin/product', ['productCode' => 'admin-active-001']);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('admin-active-001', $ro->body['productCode']);
        $this->assertSame('管理画面用 商品A', $ro->body['productName']);
        $this->assertSame(3500, $ro->body['price02']);
        $this->assertSame(1, $ro->body['productStatus']);
        // Admin-only columns surface.
        $this->assertArrayHasKey('note', $ro->body);
        $this->assertArrayHasKey('searchWord', $ro->body);
    }

    public function testOnGetUnknownProductReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\ProductNotFoundException::class);

        $this->resource->get('page://self/admin/product', ['productCode' => 'does-not-exist']);
    }

    public function testOnGetWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->get('page://self/admin/product', ['productCode' => 'admin-active-001']);
    }

    // ----------- onPost (doCreateProduct) -----------

    public function testOnPostCreatesProductAndReturns201(): void
    {
        $ro = $this->resource->post('page://self/admin/product', [
            'productCode' => 'wave8-resource-001',
            'productName' => 'Wave 8 Resource',
            'price02' => 2500,
            'stock' => 30,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('wave8-resource-001', $ro->body['productCode']);
        $this->assertSame(1, $ro->body['productStatus']);
        $this->assertArrayHasKey('Location', $ro->headers);
    }

    public function testOnPostDuplicateReturns409(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\ProductCodeAlreadyInUseException::class);

        $this->resource->post('page://self/admin/product', [
            'productCode' => 'admin-active-001',
            'productName' => 'duplicate',
            'price02' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/product', [
            'productCode' => 'wave8-no-csrf-001',
            'productName' => 'No CSRF',
            'price02' => 100,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPostWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->post('page://self/admin/product', [
            'productCode' => 'wave8-noadm-001',
            'productName' => 'No admin',
            'price02' => 100,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    // ----------- onPut (doUpdateProduct) -----------

    public function testOnPutPartialUpdateReturns200(): void
    {
        $ro = $this->resource->put('page://self/admin/product', [
            'productCode' => 'admin-active-001',
            'productName' => '更新後',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('更新後', $ro->body['productName']);
        // Price is unchanged.
        $this->assertSame(3500, $ro->body['price02']);
    }

    public function testOnPutUnknownProductReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\ProductNotFoundException::class);

        $this->resource->put('page://self/admin/product', [
            'productCode' => 'does-not-exist',
            'productName' => 'whatever',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPutWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->put('page://self/admin/product', [
            'productCode' => 'admin-active-001',
            'productName' => 'no admin',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    // ----------- onDelete (doDeleteProduct) -----------

    public function testOnDeleteHappyPathReturns200(): void
    {
        $ro = $this->resource->delete('page://self/admin/product', [
            'productCode' => 'admin-active-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('admin-active-001', $ro->body['productCode']);
        $this->assertFalse($ro->body['alreadyDeleted']);
    }

    public function testOnDeleteHtmlContextRedirectsToProductList(): void
    {
        $context = getenv('APP_CONTEXT');
        putenv('APP_CONTEXT=html-test-hal-app');
        $ro = null;

        try {
            $ro = $this->resource->delete('page://self/admin/product', [
                'productCode' => 'admin-active-001',
                'csrfToken' => FakeCsrfToken::TOKEN,
            ]);
        } finally {
            $context === false ? putenv('APP_CONTEXT') : putenv('APP_CONTEXT=' . $context);
        }

        assert($ro !== null);
        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/admin/product-list', $ro->headers['Location']);
        $this->assertSame('admin-active-001', $ro->body['productCode']);
    }

    public function testOnDeleteAlreadyDeletedReturnsAlreadyDeleted(): void
    {
        // Fake context is static-fixture based; replay-after-mutation is
        // covered by the SQL suite. The withdrawn fixture directly
        // exercises the idempotent already-deleted branch.
        $ro = $this->resource->delete('page://self/admin/product', [
            'productCode' => 'admin-withdrawn-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertTrue($ro->body['alreadyDeleted']);
    }

    public function testOnDeleteUnknownReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\ProductNotFoundException::class);

        $this->resource->delete('page://self/admin/product', [
            'productCode' => 'does-not-exist',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnDeleteMissingCsrfReturns403(): void
    {
        $ro = $this->resource->delete('page://self/admin/product', [
            'productCode' => 'admin-active-001',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }
}
